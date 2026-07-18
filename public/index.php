<?php
declare(strict_types=1);

use App\Database\Database;
use App\Http\Response;
use App\Http\Router;
use App\Http\Request;
use App\Modules\Admin\AdminController;
use App\Modules\Auth\AuthService;
use App\Modules\Auth\LoginRateLimiter;
use App\Modules\Install\InstallController;
use App\Modules\Install\InstallerService;
use App\Support\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

session_name((string) Env::get('SESSION_NAME', 'mybsphp_session'));
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'cookie_lifetime' => (int) Env::get('SESSION_LIFETIME', 7200),
    'use_strict_mode' => true,
]);

$router = new Router();
$request = new Request();
$installer = new InstallerService(dirname(__DIR__));
$installController = new InstallController($installer, $request);

$router->get('/install', static fn () => $installController->show());
$router->post('/install', static fn () => $installController->submit());

$installed = $installer->isInstalled() || Env::get('APP_INSTALLED', false) === true;
$router->get('/', static function () use ($installed): void {
    if (!$installed) {
        Response::redirect('/install');
    }
    $database = 'ok';
    try {
        Database::connection()->query('SELECT 1');
    } catch (Throwable) {
        $database = 'not-configured';
    }
    Response::json([
        'name' => Env::get('APP_NAME', 'MyBsPHP'),
        'version' => '0.1.0-dev',
        'status' => 'ok',
        'database' => $database,
    ]);
});

$router->get('/api/health', static fn () => Response::json(['ok' => true, 'time' => date(DATE_ATOM)]));

if ($installed) {
    $adminPath = '/' . trim((string) Env::get('ADMIN_PATH', 'admin'), '/');
    $adminController = new AdminController(new AuthService(), new LoginRateLimiter(), $request);
    $router->get($adminPath, static fn () => $adminController->dashboard());
    $router->get($adminPath . '/', static fn () => $adminController->dashboard());
    $router->get($adminPath . '/login', static fn () => $adminController->loginPage());
    $router->post($adminPath . '/login', static fn () => $adminController->login());
    $router->post($adminPath . '/logout', static fn () => $adminController->logout());
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
