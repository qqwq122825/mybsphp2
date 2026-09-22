<?php

use App\Http\Controllers\ProxyLinkController;
use Illuminate\Support\Facades\Route;

// Machine-only POST endpoint. The 128-bit random HTTPS path is a secret capability;
// no browser session, CSRF token or separate node key is needed.
Route::post('/{path}', ProxyLinkController::class)
    ->where('path', 'lk_[a-f0-9]{32}')
    ->middleware('throttle:60,1')
    ->name('proxy.link');
