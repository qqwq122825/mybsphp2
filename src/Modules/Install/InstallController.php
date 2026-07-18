<?php
declare(strict_types=1);

namespace App\Modules\Install;

use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Support\Env;
use App\Support\View;
use Throwable;

final class InstallController
{
    public function __construct(
        private readonly InstallerService $installer,
        private readonly Request $request
    ) {
    }

    public function show(): never
    {
        $this->captureReinstallAuthorization();
        $step = $this->normalizeStep($this->request->string('step', 'environment'));
        $isCompletedSession = $step === 'complete'
            && isset($_SESSION['install']['completed_admin_path']);
        if (
            $this->installer->isInstalled()
            && !$this->reinstallAuthorized()
            && !$isCompletedSession
        ) {
            Response::redirect('/' . $this->installedAdminPath() . '/login');
        }
        $this->render($step);
    }

    public function submit(): never
    {
        if ($this->installer->isInstalled() && !$this->reinstallAuthorized()) {
            Response::redirect('/' . $this->installedAdminPath() . '/login');
        }
        $step = $this->normalizeStep($this->request->string('step', 'database'));
        $token = $this->request->string('_token', $this->request->string('_csrf'));
        if (!Csrf::verify($token)) {
            $this->render($step, ['页面已刷新，请重新提交。']);
        }

        try {
            match ($step) {
                'database' => $this->saveDatabase(),
                'site' => $this->saveSite(),
                'admin' => $this->completeInstallation(),
                default => Response::redirect('/install?step=database'),
            };
        } catch (Throwable $exception) {
            $this->render($step, [$exception->getMessage()]);
        }
    }

    private function saveDatabase(): never
    {
        $values = [
            'db_host' => $this->request->string('db_host', '127.0.0.1'),
            'db_port' => $this->request->string('db_port', '3306'),
            'db_database' => $this->request->string('db_database', 'mybsphp'),
            'db_username' => $this->request->string('db_username', 'root'),
            'db_password' => $this->request->string('db_password'),
            'create_database' => $this->request->boolean('create_database', true),
            'overwrite_database' => $this->request->boolean('overwrite_database'),
        ];
        $this->installer->testDatabase($values);
        $_SESSION['install']['database'] = $values;
        Response::redirect('/install?step=site');
    }

    private function saveSite(): never
    {
        $values = [
            'app_name' => $this->request->string('app_name', 'MyBsPHP'),
            'app_url' => $this->request->string('app_url', $this->defaultUrl()),
            'admin_path' => $this->request->string('admin_path', 'admin'),
        ];
        $this->installer->validateSite($values);
        $_SESSION['install']['site'] = $values;
        Response::redirect('/install?step=admin');
    }

    private function completeInstallation(): never
    {
        $database = $_SESSION['install']['database'] ?? null;
        $site = $_SESSION['install']['site'] ?? null;
        if (!is_array($database) || !is_array($site)) {
            Response::redirect('/install?step=database');
        }
        $admin = [
            'username' => $this->request->string('admin_username'),
            'password' => $this->request->string('admin_password'),
            'password_confirmation' => $this->request->string('admin_password_confirmation'),
            'secondary_password' => $this->request->string('admin_secondary_password'),
            'secondary_password_confirmation' => $this->request->string('admin_secondary_password_confirmation'),
            'confirm_overwrite' => $this->request->boolean('confirm_overwrite'),
            'overwrite_database_name' => $this->request->string('overwrite_database_name'),
        ];
        $this->installer->install(
            $database,
            $site,
            $admin,
            $this->reinstallAuthorized()
        );
        $_SESSION['install']['completed_admin_path'] = $site['admin_path'];
        unset(
            $_SESSION['install']['database'],
            $_SESSION['install']['site'],
            $_SESSION['install']['reinstall_authorized']
        );
        Csrf::rotate();
        Response::redirect('/install?step=complete');
    }

    /** @param list<string> $errors */
    private function render(string $step, array $errors = []): never
    {
        $checks = $this->installer->environmentChecks();
        if ($step === 'environment') {
            foreach ($checks as $check) {
                if (!$check['ok']) {
                    $errors[] = '当前环境还有未通过的检查项。';
                    break;
                }
            }
        }
        $defaults = [
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'mybsphp',
            'db_username' => 'root',
            'db_password' => '',
            'create_database' => true,
            'overwrite_database' => false,
            'app_name' => 'MyBsPHP',
            'app_url' => $this->defaultUrl(),
            'admin_path' => 'admin',
            'admin_username' => 'admin',
        ];
        $values = array_merge(
            $defaults,
            is_array($_SESSION['install']['database'] ?? null) ? $_SESSION['install']['database'] : [],
            is_array($_SESSION['install']['site'] ?? null) ? $_SESSION['install']['site'] : [],
            $_POST
        );
        if ($step === 'complete') {
            $values['admin_path'] = $_SESSION['install']['completed_admin_path'] ?? 'admin';
        }

        Response::html(View::render('install/wizard.php', [
            'step' => $step,
            'errors' => array_values(array_unique($errors)),
            'values' => $values,
            'checks' => $checks,
            'csrfToken' => Csrf::token(),
            'completedAdminPath' => $_SESSION['install']['completed_admin_path'] ?? 'admin',
            'reinstallAuthorized' => $this->reinstallAuthorized(),
        ]));
    }

    private function normalizeStep(string $step): string
    {
        return in_array($step, ['environment', 'database', 'site', 'admin', 'complete'], true)
            ? $step
            : 'environment';
    }

    private function defaultUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080');
    }

    private function installedAdminPath(): string
    {
        return (string) Env::get('ADMIN_PATH', 'admin');
    }

    private function captureReinstallAuthorization(): void
    {
        $token = $this->request->string('reinstall_token');
        if ($token !== '' && $this->installer->consumeReinstallAuthorization($token)) {
            session_regenerate_id(true);
            $_SESSION['install']['reinstall_authorized'] = true;
            $_SESSION['install']['database']['overwrite_database'] = true;
        }
    }

    private function reinstallAuthorized(): bool
    {
        return ($_SESSION['install']['reinstall_authorized'] ?? false) === true;
    }
}
