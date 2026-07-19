<?php

use App\Console\Commands\IssueReinstallationLink;
use App\Http\ApiResponse;
use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\EnsureAdministratorAuthenticated;
use App\Http\Middleware\EnsureAdminPath;
use App\Http\Middleware\PreserveWorkspaceReturnUrl;
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
    )
    ->withCommands([IssueReinstallationLink::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AttachRequestId::class);
        $middleware->validateCsrfTokens(except: ['AppEn.php']);
        $middleware->alias([
            'admin.auth' => EnsureAdministratorAuthenticated::class,
            'admin.path' => EnsureAdminPath::class,
            'admin.workspace' => PreserveWorkspaceReturnUrl::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

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
                    request: $request,
                ),
                default => ApiResponse::error(
                    'INTERNAL_ERROR',
                    '服务暂时异常',
                    500,
                    request: $request,
                ),
            };
        });
    })->create();
