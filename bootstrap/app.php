<?php

use App\Console\Commands\IssueReinstallationLink;
use App\Http\ApiResponse;
use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\EnsureAdministratorAuthenticated;
use App\Http\Middleware\EnsureAdminPath;
use App\Http\Middleware\EnsureAgentAuthenticated;
use App\Http\Middleware\EnsureClientServiceEnabled;
use App\Http\Middleware\PreserveWorkspaceReturnUrl;
use App\Support\OperationalMode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/proxy.php');
        },
    )
    ->withCommands([IssueReinstallationLink::class])
    ->withMiddleware(function (Middleware $middleware): void {
        // 信任 Cloudflare / Nginx 转发头，按真实访问协议生成跳转（http 就 http，https 就 https）
        $middleware->trustProxies(at: '*');
        $middleware->prepend(AttachRequestId::class);
        $middleware->validateCsrfTokens(except: ['AppEn.php']);
        $middleware->alias([
            'admin.auth' => EnsureAdministratorAuthenticated::class,
            'agent.auth' => EnsureAgentAuthenticated::class,
            'admin.path' => EnsureAdminPath::class,
            'admin.workspace' => PreserveWorkspaceReturnUrl::class,
            'client.enabled' => EnsureClientServiceEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['secondary_password', 'secondary_password_confirmation', 'current_secondary_password']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('AppEn.php') || $request->is('lk_*'),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->is('AppEn.php')) {
                return null;
            }

            $debugDetails = app(OperationalMode::class)->isDebug()
                ? [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
                : null;

            return match (true) {
                $exception instanceof ValidationException => ApiResponse::error(
                    code: 'VALIDATION_FAILED',
                    message: '请求参数校验失败',
                    status: 422,
                    details: ['fields' => $exception->errors()],
                    request: $request,
                ),
                $exception instanceof AuthenticationException => ApiResponse::error(
                    'AUTHENTICATION_REQUIRED',
                    '请先登录',
                    401,
                    request: $request,
                ),
                $exception instanceof AuthorizationException => ApiResponse::error(
                    'PERMISSION_DENIED',
                    '当前账号没有执行此操作的权限',
                    403,
                    request: $request,
                ),
                $exception instanceof ModelNotFoundException,
                $exception instanceof NotFoundHttpException => ApiResponse::error(
                    'NOT_FOUND',
                    '请求的资源不存在',
                    404,
                    request: $request,
                ),
                $exception instanceof HttpExceptionInterface => ApiResponse::error(
                    code: 'HTTP_'.$exception->getStatusCode(),
                    message: $exception->getStatusCode() >= 500 ? '服务暂时异常' : '请求处理失败',
                    status: $exception->getStatusCode(),
                    details: $exception->getStatusCode() >= 500 ? $debugDetails : null,
                    request: $request,
                ),
                default => ApiResponse::error(
                    'INTERNAL_ERROR',
                    '服务暂时异常',
                    500,
                    $debugDetails,
                    request: $request,
                ),
            };
        });
    })->create();
