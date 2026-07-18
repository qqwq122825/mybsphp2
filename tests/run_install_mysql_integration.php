<?php
declare(strict_types=1);

use App\Modules\Install\InstallerService;

require dirname(__DIR__) . '/vendor/autoload.php';

$host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('TEST_DB_PORT') ?: 3306);
$username = getenv('TEST_DB_USERNAME') ?: 'root';
$password = getenv('TEST_DB_PASSWORD') ?: '';
$databaseName = 'mybsphp_install_integration';
$fixtureRoot = sys_get_temp_dir() . '/mybsphp-install-' . bin2hex(random_bytes(5));

$server = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$removeTree = static function (string $path) use (&$removeTree): void {
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $target = $path . '/' . $name;
        is_dir($target) ? $removeTree($target) : unlink($target);
    }
    rmdir($path);
};

try {
    mkdir($fixtureRoot . '/database/migrations', 0750, true);
    foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [] as $migration) {
        copy($migration, $fixtureRoot . '/database/migrations/' . basename($migration));
    }

    $server->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    $server->exec(
        "CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $server->exec("CREATE TABLE `{$databaseName}`.old_install_marker (id INT PRIMARY KEY)");

    $installer = new InstallerService($fixtureRoot);
    $database = [
        'db_host' => $host,
        'db_port' => (string) $port,
        'db_database' => $databaseName,
        'db_username' => $username,
        'db_password' => $password,
        'create_database' => true,
        'overwrite_database' => true,
    ];
    $site = [
        'app_name' => 'Install Integration',
        'app_url' => 'http://127.0.0.1:8090',
        'admin_path' => 'admin',
    ];
    $admin = [
        'username' => 'admin',
        'password' => 'abc123',
        'password_confirmation' => 'abc123',
        'secondary_password' => 'xyz789',
        'secondary_password_confirmation' => 'xyz789',
        'confirm_overwrite' => true,
        'overwrite_database_name' => $databaseName,
    ];

    $installer->install($database, $site, $admin);

    $db = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $databaseName
        ),
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $row = $db->query(
        "SELECT u.username, u.password_hash, u.secondary_password_hash, r.code
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE u.username = 'admin'"
    )->fetch(PDO::FETCH_ASSOC);
    if (
        !is_array($row)
        || $row['code'] !== 'super_admin'
        || !password_verify('abc123', (string) $row['password_hash'])
        || !password_verify('xyz789', (string) $row['secondary_password_hash'])
    ) {
        throw new RuntimeException('管理员、角色或密码哈希验证失败。');
    }
    if (
        !is_file($fixtureRoot . '/storage/installed.lock')
        || !str_contains(
            (string) file_get_contents($fixtureRoot . '/.env'),
            'ADMIN_SECONDARY_PASSWORD_ENABLED="true"'
        )
    ) {
        throw new RuntimeException('安装锁或环境配置验证失败。');
    }

    $token = bin2hex(random_bytes(24));
    file_put_contents(
        $fixtureRoot . '/storage/reinstall-token.json',
        json_encode([
            'token_hash' => hash('sha256', $token),
            'expires_at' => time() + 60,
        ], JSON_THROW_ON_ERROR)
    );
    if (!$installer->authorizeReinstall($token) || $installer->authorizeReinstall('wrong-token')) {
        throw new RuntimeException('重装令牌验证失败。');
    }
    if (
        !$installer->consumeReinstallAuthorization($token)
        || $installer->authorizeReinstall($token)
    ) {
        throw new RuntimeException('重装令牌一次性消费验证失败。');
    }

    echo "Install overwrite and reinstall-token integration tests passed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    $server->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    $removeTree($fixtureRoot);
}

exit($exitCode ?? 0);
