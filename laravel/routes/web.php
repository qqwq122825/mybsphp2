<?php

use App\Auth\AdminPermission;
use App\Http\Controllers\AdminAdministratorController;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminCardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', SiteController::class)->name('site.home');
Route::get('/demo/activate', fn () => response()->file(public_path('demo-activate.html')))
    ->name('demo.activate');

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
            Route::get('/dashboard', AdminDashboardController::class)
                ->middleware('can:'.AdminPermission::DashboardView->value)
                ->name('admin.dashboard');

            Route::get('/administrators', [AdminAdministratorController::class, 'index'])
                ->middleware('can:'.AdminPermission::AdministratorsView->value)
                ->name('admin.administrators.index');
            Route::post('/administrators', [AdminAdministratorController::class, 'store'])
                ->middleware('can:'.AdminPermission::AdministratorsCreate->value)
                ->name('admin.administrators.store');
            Route::put('/administrators/security', [AdminAdministratorController::class, 'updateSecurity'])
                ->name('admin.administrators.security.update');
            Route::put('/administrators/{managedAdministrator}', [AdminAdministratorController::class, 'update'])
                ->middleware('can:'.AdminPermission::AdministratorsUpdate->value)
                ->name('admin.administrators.update');

            Route::get('/applications', [AdminApplicationController::class, 'index'])
                ->middleware('can:'.AdminPermission::ApplicationsView->value)
                ->name('admin.applications.index');
            Route::get('/applications/create', [AdminApplicationController::class, 'create'])
                ->middleware('can:'.AdminPermission::ApplicationsCreate->value)
                ->name('admin.applications.create');
            Route::post('/applications', [AdminApplicationController::class, 'store'])
                ->middleware('can:'.AdminPermission::ApplicationsCreate->value)
                ->name('admin.applications.store');
            Route::get('/applications/{application}/edit', [AdminApplicationController::class, 'edit'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.applications.edit');
            Route::put('/applications/{application}', [AdminApplicationController::class, 'update'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.applications.update');
            Route::post('/applications/{application}/toggle', [AdminApplicationController::class, 'toggle'])
                ->middleware('can:'.AdminPermission::ApplicationsStatusUpdate->value)
                ->name('admin.applications.toggle');

            Route::get('/cards', [AdminCardController::class, 'index'])
                ->middleware('can:'.AdminPermission::CardsView->value)
                ->name('admin.cards.index');
            Route::post('/card-types', [AdminCardController::class, 'storeType'])
                ->middleware('can:'.AdminPermission::CardTypesManage->value)
                ->name('admin.card-types.store');
            Route::post('/card-batches', [AdminCardController::class, 'generateBatch'])
                ->middleware('can:'.AdminPermission::BatchesCreate->value)
                ->name('admin.card-batches.store');
            Route::get('/card-issues', [AdminCardController::class, 'issues'])
                ->middleware('can:'.AdminPermission::CardIssuesView->value)
                ->name('admin.card-issues.index');
            Route::post('/card-issues', [AdminCardController::class, 'storeIssue'])
                ->middleware('can:'.AdminPermission::CardsIssue->value)
                ->name('admin.card-issues.store');

            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        });
    });
