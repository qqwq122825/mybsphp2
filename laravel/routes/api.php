<?php

use App\Http\ApiResponse;
use App\Http\Controllers\CardActivationController;
use Illuminate\Support\Facades\Route;

Route::get('/v2/ping', fn () => ApiResponse::success([
    'service' => 'MyBsPHP2',
    'status' => 'ok',
]));

Route::post('/v2/demo/activate', [CardActivationController::class, 'activate'])
    ->middleware('throttle:30,1');
