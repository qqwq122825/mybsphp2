<?php

use App\Http\ApiResponse;
use App\Http\Controllers\CardActivationController;
use App\Http\Controllers\AccountAuthController;
use App\Http\Controllers\ClientSessionController;
use App\Http\Controllers\ApplicationPublicConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/v2/ping', fn () => ApiResponse::success([
    'service' => 'MyBsPHP2',
    'status' => 'ok',
]));

// 客户端已经持有软件代号时，可直接用 code 获取启动配置。
Route::get('/v2/software/{application:code}/public-config', ApplicationPublicConfigController::class)
    ->middleware(['client.enabled', 'throttle:60,1']);

Route::post('/v2/demo/activate', [CardActivationController::class, 'activate'])
    ->middleware('throttle:30,1');

Route::prefix('/v2/apps/{application}')->middleware(['client.enabled', 'throttle:60,1'])->group(function (): void {
    Route::get('/public-config', ApplicationPublicConfigController::class);
    Route::post('/sessions/handle', [ClientSessionController::class, 'handle']);
    Route::post('/sessions/heartbeat', [ClientSessionController::class, 'heartbeat']);
    Route::post('/accounts/exists', [AccountAuthController::class, 'exists']);
    Route::post('/accounts/register', [AccountAuthController::class, 'register']);
    Route::post('/accounts/login', [AccountAuthController::class, 'login']);
    Route::post('/accounts/recharge', [AccountAuthController::class, 'recharge']);
});
