<?php
declare(strict_types=1);

namespace App\Modules\Install;

use App\Database\MigrationRunner;
use App\Support\Env;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class InstallerService
{
    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? dirname(__DIR__, 3);
    }

    public function isInstalled(): bool
    {
        return is_file($this->root . '/storage/installed.lock')
            || Env::get('APP_INSTALLED', false) === true;
    }

    public function authorizeReinstall(string $token): bool
    {
        if ($token === '') {
            return false;
        }
        $file = $this->root . '/storage/reinstall-token.json';
        if (!is_file($file)) {
            return false;
        }
        $payload = json_decode((string) file_get_contents($file), true);
        if (
            !is_array($payload)
            || !is_string($payload['token_hash'] ?? null)
            || (int) ($payload['expires_at'] ?? 0) < time()
        ) {
            @unlink($file);
            return false;
        }
        return hash_equals(
            (string) $payload['token_hash'],
            hash('sha256', $token)
        );
    }

    public function consumeReinstallAuthorization(string $token): bool
    {
        if (!$this->authorizeReinstall($token)) {
            return false;
        }
        $this->clearReinstallAuthorization();
        return true;
    }

    /** @return array<string, array{ok: bool, value: string}> */
    public function environmentChecks(): array
    {
        $extensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'mbstring'];
        $checks = [
            'PHP >= 8.1' => [
                'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'value' => PHP_VERSION,
            ],
        ];
        foreach ($extensions as $extension) {
            $checks["扩展 {$extension}"] = [
                'ok' => extension_loaded($extension),
                'value' => extension_loaded($extension) ? '已启用' : '缺失',
            ];
        }
        $checks['项目目录可写'] = [
            'ok' => is_writable($this->root),
            'value' => is_writable($this->root) ? '正常' : '请调整目录权限',
        ];
        return $checks;
    }

    /** @param array<string, string|bool> $values */
    public function testDatabase(array $values): void
    {
        $host = trim((string) ($values['db_host'] ?? '127.0.0.1'));
        $port = (int) ($values['db_port'] ?? 3306);
        $database = trim((string) ($values['db_database'] ?? ''));
        $username = trim((string) ($values['db_username'] ?? 'root'));
        $password = (string) ($values['db_password'] ?? '');

        if ($host === '' || $port < 1 || $port > 65535) {
            throw new RuntimeException('数据库地址或端口格式不正确。');
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $database)) {
            throw new RuntimeException('数据库名只支持字母、数字和下划线。');
        }

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
            $username,
            $password,
            $this->pdoOptions()
        );
        if ((bool) ($values['create_database'] ?? false)) {
            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $database
            ));
        }
        $pdo->exec(sprintf('USE `%s`', $database));
        $pdo->query('SELECT 1');
        if (
            $this->databaseTableCount($pdo, $database) > 0
            && !(bool) ($values['overwrite_database'] ?? false)
        ) {
            throw new RuntimeException(
                "数据库 {$database} 已包含数据表。请选择其他数据库，"
                . '或明确勾选“覆盖现有数据库”后继续。'
            );
        }
    }

    /** @param array<string, mixed> $database @param array<string, mixed> $site @param array<string, mixed> $admin */
    public function install(
        array $database,
        array $site,
        array $admin,
        bool $reinstallAuthorized = false
    ): void
    {
        if ($this->isInstalled() && !$reinstallAuthorized) {
            throw new RuntimeException('系统已经完成安装。');
        }

        $this->validateSite($site);
        $this->validateAdministrator($admin);
        $this->validateOverwriteConfirmation($database, $admin);
        $this->testDatabase($database);
        if ((bool) ($database['overwrite_database'] ?? false)) {
            $this->resetDatabase($database);
        }

        $pdo = $this->databaseConnection($database);
        (new MigrationRunner($pdo, $this->root . '/database/migrations'))->run();

        $pdo->beginTransaction();
        try {
            $passwordHash = password_hash((string) $admin['password'], PASSWORD_DEFAULT);
            $secondaryPasswordHash = password_hash(
                (string) $admin['secondary_password'],
                PASSWORD_DEFAULT
            );
            $statement = $pdo->prepare(
                'INSERT INTO users (
                    username,
                    password_hash,
                    secondary_password_hash,
                    secondary_password_required,
                    secondary_password_changed_at,
                    status
                ) VALUES (
                    :username,
                    :password_hash,
                    :secondary_password_hash,
                    1,
                    CURRENT_TIMESTAMP,
                    1
                )'
            );
            $statement->execute([
                'username' => (string) $admin['username'],
                'password_hash' => $passwordHash,
                'secondary_password_hash' => $secondaryPasswordHash,
            ]);
            $userId = (int) $pdo->lastInsertId();
            $this->grantSuperAdministrator($pdo, $userId);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                throw new RuntimeException(
                    '管理员用户名已存在，请更换用户名或使用空数据库重新安装。',
                    0,
                    $exception
                );
            }
            throw $exception;
        }

        $appKey = bin2hex(random_bytes(32));
        $env = [
            'APP_NAME' => (string) $site['app_name'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim((string) $site['app_url'], '/'),
            'APP_KEY' => $appKey,
            'APP_INSTALLED' => 'true',
            'ADMIN_PATH' => (string) $site['admin_path'],
            'ADMIN_SECONDARY_PASSWORD_ENABLED' => 'true',
            'DB_HOST' => (string) $database['db_host'],
            'DB_PORT' => (string) $database['db_port'],
            'DB_DATABASE' => (string) $database['db_database'],
            'DB_USERNAME' => (string) $database['db_username'],
            'DB_PASSWORD' => (string) ($database['db_password'] ?? ''),
            'SESSION_NAME' => 'mybsphp_session',
            'SESSION_LIFETIME' => '7200',
        ];
        $this->writeEnvironment($env);
        $this->writeInstallLock();
        $this->clearReinstallAuthorization();
    }

    /** @param array<string, mixed> $site */
    public function validateSite(array $site): void
    {
        $name = trim((string) ($site['app_name'] ?? ''));
        $url = trim((string) ($site['app_url'] ?? ''));
        $adminPath = trim((string) ($site['admin_path'] ?? 'admin'));
        if ($name === '' || mb_strlen($name) > 80) {
            throw new RuntimeException('网站名称需要填写，最长80个字符。');
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('网站 URL 格式不正确。');
        }
        if (!preg_match('/^[A-Za-z0-9_-]{3,64}$/', $adminPath)) {
            throw new RuntimeException('后台路径需为3至64位字母、数字、短横线或下划线。');
        }
        if (in_array(strtolower($adminPath), ['install', 'api', 'assets', 'storage'], true)) {
            throw new RuntimeException('该后台路径属于系统保留路径。');
        }
    }

    /** @param array<string, mixed> $admin */
    public function validateAdministrator(array $admin): void
    {
        $username = trim((string) ($admin['username'] ?? ''));
        $password = (string) ($admin['password'] ?? '');
        $secondaryPassword = (string) ($admin['secondary_password'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            throw new RuntimeException('管理员账号需为3至50位字母、数字、点、短横线或下划线。');
        }
        if (strlen($password) < 6) {
            throw new RuntimeException('管理员密码至少6位。');
        }
        if (!hash_equals($password, (string) ($admin['password_confirmation'] ?? ''))) {
            throw new RuntimeException('两次输入的管理员密码不一致。');
        }
        if (strlen($secondaryPassword) < 6) {
            throw new RuntimeException('管理员二级密码至少6位。');
        }
        if (!hash_equals(
            $secondaryPassword,
            (string) ($admin['secondary_password_confirmation'] ?? '')
        )) {
            throw new RuntimeException('两次输入的管理员二级密码不一致。');
        }
    }

    /** @param array<string, mixed> $values */
    private function databaseConnection(array $values): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                (string) $values['db_host'],
                (int) $values['db_port'],
                (string) $values['db_database']
            ),
            (string) $values['db_username'],
            (string) ($values['db_password'] ?? ''),
            $this->pdoOptions()
        );
    }

    /** @return array<int, mixed> */
    private function pdoOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    private function grantSuperAdministrator(PDO $pdo, int $userId): void
    {
        $roleId = $pdo->query("SELECT id FROM roles WHERE code = 'super_admin' LIMIT 1")->fetchColumn();
        if ($roleId === false) {
            throw new RuntimeException('超级管理员角色初始化失败。');
        }
        $statement = $pdo->prepare(
            'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
        );
        $statement->execute(['user_id' => $userId, 'role_id' => (int) $roleId]);
    }

    /** @param array<string, string> $values */
    private function writeEnvironment(array $values): void
    {
        $lines = [];
        foreach ($values as $key => $value) {
            $escaped = str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '', ''], $value);
            $lines[] = sprintf('%s="%s"', $key, $escaped);
        }
        $temporary = $this->root . '/.env.installing';
        if (file_put_contents($temporary, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('写入环境配置失败。');
        }
        @chmod($temporary, 0600);
        if (!rename($temporary, $this->root . '/.env')) {
            throw new RuntimeException('保存环境配置失败。');
        }
    }

    private function writeInstallLock(): void
    {
        $storage = $this->root . '/storage';
        if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
            throw new RuntimeException('创建 storage 目录失败。');
        }
        $payload = json_encode([
            'installed_at' => date(DATE_ATOM),
            'version' => '0.1.0-dev',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (file_put_contents($storage . '/installed.lock', $payload . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('写入安装锁失败。');
        }
    }

    /** @param array<string, mixed> $database @param array<string, mixed> $admin */
    private function validateOverwriteConfirmation(array $database, array $admin): void
    {
        if (!(bool) ($database['overwrite_database'] ?? false)) {
            return;
        }
        $databaseName = (string) ($database['db_database'] ?? '');
        if (!(bool) ($admin['confirm_overwrite'] ?? false)) {
            throw new RuntimeException('覆盖安装前需要勾选最终确认。');
        }
        if (!hash_equals(
            $databaseName,
            (string) ($admin['overwrite_database_name'] ?? '')
        )) {
            throw new RuntimeException("请输入数据库名 {$databaseName} 以确认覆盖安装。");
        }
    }

    /** @param array<string, mixed> $values */
    private function resetDatabase(array $values): void
    {
        $database = trim((string) $values['db_database']);
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;charset=utf8mb4',
                (string) $values['db_host'],
                (int) $values['db_port']
            ),
            (string) $values['db_username'],
            (string) ($values['db_password'] ?? ''),
            $this->pdoOptions()
        );
        $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
        $pdo->exec(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $database
        ));
    }

    private function databaseTableCount(PDO $pdo, string $database): int
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = :database
               AND table_type = "BASE TABLE"'
        );
        $statement->execute(['database' => $database]);
        return (int) $statement->fetchColumn();
    }

    private function clearReinstallAuthorization(): void
    {
        $file = $this->root . '/storage/reinstall-token.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
