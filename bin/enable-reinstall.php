<?php
declare(strict_types=1);

use App\Support\Env;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
Env::load($root . '/.env');

$storage = $root . '/storage';
if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
    fwrite(STDERR, "创建 storage 目录失败。\n");
    exit(1);
}

$token = bin2hex(random_bytes(24));
$expiresAt = time() + 1800;
$payload = json_encode([
    'token_hash' => hash('sha256', $token),
    'expires_at' => $expiresAt,
    'created_at' => date(DATE_ATOM),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$file = $storage . '/reinstall-token.json';
if (file_put_contents($file, $payload . PHP_EOL, LOCK_EX) === false) {
    fwrite(STDERR, "创建重装令牌失败。\n");
    exit(1);
}
@chmod($file, 0600);

$appUrl = rtrim((string) Env::get('APP_URL', 'http://127.0.0.1:8090'), '/');
echo "重装入口将在 30 分钟后失效，成功安装后立即作废：\n";
echo $appUrl . '/install?reinstall_token=' . $token . PHP_EOL;
