<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Modules\Auth\AuthService;
use App\Modules\Auth\LoginRateLimiter;
use App\Support\Env;
use App\Support\View;

final class AdminController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly LoginRateLimiter $limiter,
        private readonly Request $request
    ) {
    }

    public function loginPage(array $errors = []): never
    {
        if ($this->auth->isAdministrator()) {
            Response::redirect($this->basePath());
        }
        Response::html(View::render('admin/login.php', [
            'appName' => Env::get('APP_NAME', 'MyBsPHP'),
            'adminPath' => Env::get('ADMIN_PATH', 'admin'),
            'csrfToken' => Csrf::token(),
            'errors' => $errors,
            'username' => $this->request->string('username'),
            'secondaryPasswordEnabled' => Env::get('ADMIN_SECONDARY_PASSWORD_ENABLED', false) === true,
        ]));
    }

    public function login(): never
    {
        if (!Csrf::verify($this->request->string('_token'))) {
            $this->loginPage(['页面已刷新，请重新登录。']);
        }
        $username = $this->request->string('username');
        $password = $this->request->string('password');
        $secondaryPassword = $this->request->string('secondary_password');
        $rateKey = $this->request->ip() . '|' . strtolower($username);

        if (!$this->limiter->available($rateKey)) {
            $this->loginPage([
                sprintf('尝试次数较多，请在 %d 秒后重试。', $this->limiter->retryAfter($rateKey)),
            ]);
        }
        if (
            $username === ''
            || $password === ''
            || !$this->auth->authenticateAdministrator($username, $password, $secondaryPassword)
        ) {
            $this->limiter->hit($rateKey);
            usleep(250000);
            $this->loginPage(['账号、密码或二级密码不正确。']);
        }
        $this->limiter->clear($rateKey);
        Csrf::rotate();
        Response::redirect($this->basePath());
    }

    public function dashboard(): never
    {
        $administrator = $this->auth->administrator();
        if ($administrator === null) {
            Response::redirect($this->basePath() . '/login');
        }
        Response::html(View::render('admin/dashboard.php', [
            'appName' => Env::get('APP_NAME', 'MyBsPHP'),
            'adminPath' => Env::get('ADMIN_PATH', 'admin'),
            'administrator' => $administrator,
            'csrfToken' => Csrf::token(),
        ]));
    }

    public function logout(): never
    {
        if (!Csrf::verify($this->request->string('_token'))) {
            Response::json(['ok' => false, 'message' => 'CSRF token mismatch'], 419);
        }
        $this->auth->logout();
        Csrf::rotate();
        Response::redirect($this->basePath() . '/login');
    }

    private function basePath(): string
    {
        return '/' . trim((string) Env::get('ADMIN_PATH', 'admin'), '/');
    }
}
