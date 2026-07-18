<?php

use App\Http\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/v2/ping', fn () => ApiResponse::success([
    'service' => 'MyBsPHP2',
    'status' => 'ok',
]));
