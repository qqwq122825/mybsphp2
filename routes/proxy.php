<?php

use App\Http\Controllers\ProxyLinkController;
use Illuminate\Support\Facades\Route;

// Registered outside the browser/session/CSRF middleware stack. Bearer authentication only.
Route::post('/{path}', ProxyLinkController::class)
    ->where('path', 'lk_[a-f0-9]{32}')
    ->middleware('throttle:60,1')
    ->name('proxy.link');
