<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', SiteController::class)->name('site.home');

Route::prefix('install')->group(function (): void {
    Route::get('/reinstall', [InstallController::class, 'authorizeReinstallation'])
        ->name('install.reinstall');
    Route::get('/{step?}', [InstallController::class, 'show'])
        ->where('step', 'requirements|database|site|administrator|complete')
        ->name('install.show');
    Route::post('/requirements', [InstallController::class, 'requirements'])
        ->name('install.requirements');
    Route::post('/database', [InstallController::class, 'database'])
        ->name('install.database');
    Route::post('/site', [InstallController::class, 'site'])
        ->name('install.site');
    Route::post('/complete', [InstallController::class, 'complete'])
        ->name('install.complete');
});

Route::prefix('{adminPath}')
    ->where(['adminPath' => '[A-Za-z0-9_-]{3,48}'])
    ->middleware('admin.path')
    ->group(function (): void {
        Route::get('/', [AdminAuthController::class, 'show'])->name('admin.entry');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login');

        Route::middleware('admin.auth')->group(function (): void {
            Route::get('/dashboard', AdminDashboardController::class)->name('admin.dashboard');
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        });
    });
