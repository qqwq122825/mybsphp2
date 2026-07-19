<?php

namespace App\Install;

use App\Auth\SystemAccessSynchronizer;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Throwable;

final class InstallerService
{
    public function __construct(
        private readonly InstallationManager $manager,
        private readonly EnvironmentFileWriter $environment,
        private readonly SystemAccessSynchronizer $access,
        private readonly SystemAccountResetter $accounts,
    ) {}

    /**
     * @param  array<string, mixed>  $database
     * @return array{database_exists:bool,table_count:int}
     */
    public function testDatabase(array $database): array
    {
        $values = $this->normalizeDatabase($database);

        try {
            $pdo = $this->serverConnection($values);
            $exists = $this->databaseExists($pdo, $values['database']);

            if (! $exists && $values['create_database']) {
                $this->createDatabase($pdo, $values['database']);
                $exists = true;
            }

            if (! $exists) {
                throw new InstallationException('数据库不存在，请先创建或开启自动创建。');
            }

            return [
                'database_exists' => true,
                'table_count' => $this->tableCount($pdo, $values['database']),
            ];
        } catch (InstallationException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new InstallationException(
                '数据库连接失败，请检查地址、端口、账号、密码和账号权限。',
                previous: $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $database
     * @param  array<string, mixed>  $site
     * @param  array<string, mixed>  $admin
     * @return array{admin_path:string,app_name:string,username:string,secondary_password_enabled:bool}
     */
    public function install(
        array $database,
        array $site,
        array $admin,
        bool $reinstallationAuthorized = false,
    ): array {
        if ($this->manager->isInstalled() && ! $reinstallationAuthorized) {
            throw new InstallationException('系统已经完成安装。');
        }

        $database = $this->normalizeDatabase($database);
        $this->validateSite($site);
        $this->validateAdministrator($admin);
        $probe = $this->testDatabase($database);

        if (
            $probe['table_count'] > 0
            && $database['existing_data_action'] === ExistingDataAction::ResetAll->value
        ) {
            $this->validateResetAll($database, $admin);
            $pdo = $this->serverConnection($database);
            $pdo->exec('DROP DATABASE IF EXISTS `'.$database['database'].'`');
            $this->createDatabase($pdo, $database['database']);
        }

        $this->configureInstallConnection($database);

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => 'install',
                '--force' => true,
                '--no-interaction' => true,
            ]);

            if ($exitCode !== 0) {
                throw new InstallationException('数据库迁移执行失败，请根据请求 ID 检查服务端日志。');
            }

            if (
                $probe['table_count'] > 0
                && $database['existing_data_action'] === ExistingDataAction::PreserveBusiness->value
            ) {
                $this->accounts->reset('install');
            }

            $this->access->sync('install');

            DB::connection('install')->transaction(function () use ($admin, $site): void {
                $administrator = new Administrator;
                $administrator->setConnection('install');
                $administrator->fill([
                    'username' => $admin['username'],
                    'password' => $admin['password'],
                    'secondary_password' => $admin['secondary_password_enabled']
                        ? $admin['secondary_password']
                        : null,
                    'secondary_password_required' => $admin['secondary_password_enabled'],
                    'role' => 'super_admin',
                    'status' => 'active',
                ]);
                $administrator->save();

                $superAdministratorRole = Role::on('install')
                    ->where('code', 'super_admin')
                    ->first();

                if ($superAdministratorRole === null) {
                    throw new InstallationException('超级管理员角色尚未初始化。');
                }

                DB::connection('install')->table('administrator_roles')->insert([
                    'administrator_id' => $administrator->getKey(),
                    'role_id' => $superAdministratorRole->getKey(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $now = now();
                $settings = [
                    ['key' => 'app_name', 'value' => $site['app_name'], 'type' => 'string', 'group' => 'site'],
                    ['key' => 'app_url', 'value' => $site['app_url'], 'type' => 'string', 'group' => 'site'],
                    ['key' => 'app_timezone', 'value' => $site['timezone'], 'type' => 'string', 'group' => 'site'],
                    ['key' => 'admin_path', 'value' => $site['admin_path'], 'type' => 'string', 'group' => 'security'],
                    [
                        'key' => 'admin_secondary_password_enabled',
                        'value' => $admin['secondary_password_enabled'] ? '1' : '0',
                        'type' => 'boolean',
                        'group' => 'security',
                    ],
                ];

                foreach ($settings as &$setting) {
                    $setting['created_at'] = $now;
                    $setting['updated_at'] = $now;
                }
                unset($setting);

                SystemSetting::on('install')->upsert(
                    $settings,
                    ['key'],
                    ['value', 'type', 'group', 'updated_at'],
                );
            }, 3);
        } catch (InstallationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InstallationException(
                '创建管理员或系统配置失败，请根据请求 ID 检查服务端日志。',
                previous: $exception,
            );
        }

        $metadata = [
            'admin_path' => $site['admin_path'],
            'app_name' => $site['app_name'],
            'secondary_password_enabled' => $admin['secondary_password_enabled'],
        ];

        $this->environment->update([
            'APP_NAME' => $site['app_name'],
            'APP_URL' => $site['app_url'],
            'APP_TIMEZONE' => $site['timezone'],
            'APP_INSTALLED' => false,
            'ADMIN_PATH' => $site['admin_path'],
            'ADMIN_SECONDARY_PASSWORD_ENABLED' => $admin['secondary_password_enabled'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $database['host'],
            'DB_PORT' => $database['port'],
            'DB_DATABASE' => $database['database'],
            'DB_USERNAME' => $database['username'],
            'DB_PASSWORD' => $database['password'],
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ]);
        $this->manager->writeLock([
            ...$metadata,
            'admin_username' => $admin['username'],
        ]);
        $this->environment->update(['APP_INSTALLED' => true]);

        return [
            ...$metadata,
            'username' => $admin['username'],
        ];
    }

    /**
     * @param  array<string, mixed>  $site
     */
    public function validateSite(array $site): void
    {
        $reserved = ['api', 'assets', 'install', 'storage', 'up', 'vendor'];

        if (! is_string($site['app_name'] ?? null) || trim($site['app_name']) === '') {
            throw new InstallationException('请填写站点名称。');
        }

        if (mb_strlen((string) $site['app_name']) > 80) {
            throw new InstallationException('站点名称最长 80 个字符。');
        }

        if (filter_var($site['app_url'] ?? null, FILTER_VALIDATE_URL) === false) {
            throw new InstallationException('站点地址格式不正确。');
        }

        if (! in_array($site['timezone'] ?? null, timezone_identifiers_list(), true)) {
            throw new InstallationException('请选择有效时区。');
        }

        $path = strtolower(trim((string) ($site['admin_path'] ?? ''), '/'));

        if (preg_match('/^[a-z0-9_-]{3,48}$/', $path) !== 1) {
            throw new InstallationException('后台入口需为 3～48 位字母、数字、短横线或下划线。');
        }

        if (in_array($path, $reserved, true)) {
            throw new InstallationException('该后台入口属于系统保留路径。');
        }
    }

    /**
     * @param  array<string, mixed>  $admin
     */
    public function validateAdministrator(array $admin): void
    {
        if (preg_match('/^[A-Za-z0-9_.-]{3,32}$/', (string) ($admin['username'] ?? '')) !== 1) {
            throw new InstallationException('管理员账号需为 3～32 位字母、数字、点、短横线或下划线。');
        }

        $password = (string) ($admin['password'] ?? '');

        if (strlen($password) < 6 || strlen($password) > 128) {
            throw new InstallationException('管理员密码需为 6～128 位。');
        }

        if (! hash_equals($password, (string) ($admin['password_confirmation'] ?? ''))) {
            throw new InstallationException('两次输入的管理员密码不一致。');
        }

        if (! ($admin['secondary_password_enabled'] ?? false)) {
            return;
        }

        $secondary = (string) ($admin['secondary_password'] ?? '');

        if (strlen($secondary) < 6 || strlen($secondary) > 128) {
            throw new InstallationException('二级密码需为 6～128 位。');
        }

        if (! hash_equals($secondary, (string) ($admin['secondary_password_confirmation'] ?? ''))) {
            throw new InstallationException('两次输入的二级密码不一致。');
        }

        if (hash_equals($password, $secondary)) {
            throw new InstallationException('主密码与二级密码需要设置为不同内容。');
        }
    }

    /**
     * @param  array<string, mixed>  $database
     * @return array{host:string,port:int,database:string,username:string,password:string,create_database:bool,existing_data_action:string}
     */
    private function normalizeDatabase(array $database): array
    {
        $host = trim((string) ($database['host'] ?? '127.0.0.1'));
        $port = (int) ($database['port'] ?? 3306);
        $name = trim((string) ($database['database'] ?? ''));
        $username = trim((string) ($database['username'] ?? ''));

        if ($host === '' || $port < 1 || $port > 65535) {
            throw new InstallationException('数据库地址或端口格式不正确。');
        }

        if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) !== 1) {
            throw new InstallationException('数据库名称只支持 1～64 位字母、数字和下划线。');
        }

        if ($username === '') {
            throw new InstallationException('请填写数据库用户名。');
        }

        $actionValue = $database['existing_data_action'] ?? null;
        $action = is_string($actionValue)
            ? ExistingDataAction::tryFrom($actionValue)
            : null;

        return [
            'host' => $host,
            'port' => $port,
            'database' => $name,
            'username' => $username,
            'password' => (string) ($database['password'] ?? ''),
            'create_database' => filter_var($database['create_database'] ?? false, FILTER_VALIDATE_BOOL),
            'existing_data_action' => $action === null
                ? ExistingDataAction::PreserveBusiness->value
                : $action->value,
        ];
    }

    /**
     * @param  array{host:string,port:int,database:string,username:string,password:string,create_database:bool,existing_data_action:string}  $database
     */
    private function serverConnection(array $database): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $database['host'], $database['port']),
            $database['username'],
            $database['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }

    private function databaseExists(PDO $pdo, string $database): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = :database',
        );
        $statement->execute(['database' => $database]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function createDatabase(PDO $pdo, string $database): void
    {
        $pdo->exec(
            'CREATE DATABASE IF NOT EXISTS `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        );
    }

    private function tableCount(PDO $pdo, string $database): int
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = :database AND table_type = "BASE TABLE"',
        );
        $statement->execute(['database' => $database]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param  array{host:string,port:int,database:string,username:string,password:string,create_database:bool,existing_data_action:string}  $database
     */
    private function configureInstallConnection(array $database): void
    {
        $connection = config('database.connections.mysql');

        if (! is_array($connection)) {
            throw new InstallationException('MySQL 连接配置不存在。');
        }

        config([
            'database.connections.install' => [
                ...$connection,
                'host' => $database['host'],
                'port' => $database['port'],
                'database' => $database['database'],
                'username' => $database['username'],
                'password' => $database['password'],
            ],
        ]);
        DB::purge('install');
    }

    /**
     * @param  array{host:string,port:int,database:string,username:string,password:string,create_database:bool,existing_data_action:string}  $database
     * @param  array<string, mixed>  $admin
     */
    private function validateResetAll(array $database, array $admin): void
    {
        if (! ($admin['confirm_reset_all'] ?? false)) {
            throw new InstallationException('清空全部数据前需要完成最终确认。');
        }

        if (! hash_equals($database['database'], (string) ($admin['reset_database_name'] ?? ''))) {
            throw new InstallationException('请输入完整数据库名称以确认清空全部数据。');
        }
    }
}
