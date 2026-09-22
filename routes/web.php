<?php

use App\Auth\AdminPermission;
use App\Http\Controllers\AdminAdministratorController;
use App\Http\Controllers\AdminAgentController;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminApplicationUserController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminCardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOperationPluginController;
use App\Http\Controllers\AdminProxyLinkController;
use App\Http\Controllers\AdminQuickOperationController;
use App\Http\Controllers\AdminRiskControlController;
use App\Http\Controllers\AdminSystemLogController;
use App\Http\Controllers\AdminSystemSettingController;
use App\Http\Controllers\AgentAuthController;
use App\Http\Controllers\AgentPortalController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LegacyGatewayController;
use App\Http\Controllers\PublicCardQueryController;
use App\Http\Controllers\SiteController;
use App\Install\InstallationManager;
use Illuminate\Support\Facades\Route;

Route::get('/', SiteController::class)->name('site.home');
Route::get('/activation-query', [PublicCardQueryController::class, 'show'])
    ->name('activation.query');
Route::post('/activation-query', [PublicCardQueryController::class, 'query'])
    ->middleware('throttle:60,1')
    ->name('activation.query.run');
Route::post('/AppEn.php', LegacyGatewayController::class)
    ->middleware(['client.enabled', 'throttle:60,1'])
    ->name('legacy.gateway');

$agentPath = app(InstallationManager::class)->agentPath();
Route::prefix($agentPath)->name('agent.')->group(function (): void {
    // Do not use a virtual *.php route here. Production Nginx treats such a
    // path as a physical PHP script before Laravel sees it, so POST requests
    // to a missing agent/index.php are converted into the 404 error subrequest.
    Route::get('/login', [AgentAuthController::class, 'show'])->name('login');
    Route::post('/login', [AgentAuthController::class, 'login'])->name('login.store');
    Route::match(['get', 'post'], '/index.php', [AgentAuthController::class, 'legacyLogin'])
        ->name('login.legacy');

    Route::middleware('agent.auth')->group(function (): void {
        Route::get('/dashboard', [AgentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/cards', [AgentPortalController::class, 'cards'])->name('cards.index');
        Route::post('/cards/{card}/action', [AgentPortalController::class, 'cardAction'])->name('cards.action');
        Route::get('/purchase', [AgentPortalController::class, 'purchase'])->name('cards.purchase');
        Route::post('/purchase', [AgentPortalController::class, 'storePurchase'])->name('cards.purchase.store');
        Route::get('/tools/query', [AgentPortalController::class, 'queryCards'])->name('tools.query');
        Route::get('/tools/freeze', [AgentPortalController::class, 'freeze'])->name('tools.freeze');
        Route::get('/tools/unfreeze', [AgentPortalController::class, 'unfreeze'])->name('tools.unfreeze');
        Route::get('/tools/unbind', [AgentPortalController::class, 'unbind'])->name('tools.unbind');
        Route::get('/finance', [AgentPortalController::class, 'finance'])->name('finance');
        Route::post('/logout', [AgentAuthController::class, 'logout'])->name('logout');
    });
});

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

        Route::middleware(['admin.auth', 'admin.workspace'])->group(function (): void {
            Route::get('/dashboard', AdminDashboardController::class)
                ->middleware('can:'.AdminPermission::DashboardView->value)
                ->name('admin.dashboard');
            Route::get('/system-logs/{type}', [AdminSystemLogController::class, 'index'])
                ->where('type', 'login|card-making|protection|client-requests|blocklist-requests')
                ->middleware('can:'.AdminPermission::AuditView->value)
                ->name('admin.system-logs.index');
            Route::post('/system-logs/{type}/maintenance', [AdminSystemLogController::class, 'maintenance'])
                ->where('type', 'login|card-making|protection|client-requests|blocklist-requests')
                ->middleware('can:'.AdminPermission::AuditView->value)
                ->name('admin.system-logs.maintenance');
            Route::get('/risk-control', [AdminRiskControlController::class, 'index'])
                ->middleware('can:'.AdminPermission::RiskEventsView->value)
                ->name('admin.risk-control.index');
            Route::get('/risk-control/center', [AdminRiskControlController::class, 'center'])
                ->middleware('can:'.AdminPermission::RiskEventsView->value)
                ->name('admin.risk-control.center');
            Route::post('/risk-control/center/release', [AdminRiskControlController::class, 'releaseSelected'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.risk-control.center.release');
            Route::put('/risk-control/applications/{application}/policy', [AdminRiskControlController::class, 'updatePolicy'])
                ->middleware('can:'.AdminPermission::RiskEventsView->value)
                ->name('admin.risk-control.policy.update');
            Route::put('/risk-control/applications/{application}/security-policy', [AdminRiskControlController::class, 'updateSecurityPolicy'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.risk-control.security-policy.update');
            Route::post('/risk-control/states/{state}/action', [AdminRiskControlController::class, 'stateAction'])
                ->middleware('can:'.AdminPermission::RiskEventsView->value)
                ->name('admin.risk-control.states.action');
            Route::post('/risk-control/device-states/{deviceState}/unblock', [AdminRiskControlController::class, 'unblockDevice'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.risk-control.device-states.unblock');
            Route::post('/risk-control/device-states/{deviceState}/status', [AdminRiskControlController::class, 'updateDeviceStatus'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.risk-control.device-states.status');
            Route::post('/risk-control/global-blocks', [AdminRiskControlController::class, 'storeGlobalBlock'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.risk-control.global-blocks.store');
            Route::delete('/risk-control/global-blocks/{globalBlock}', [AdminRiskControlController::class, 'destroyGlobalBlock'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.risk-control.global-blocks.destroy');

            Route::get('/administrators', [AdminAdministratorController::class, 'index'])
                ->middleware('can:'.AdminPermission::AdministratorsView->value)
                ->name('admin.administrators.index');
            Route::get('/administrators/create', [AdminAdministratorController::class, 'create'])
                ->middleware('can:'.AdminPermission::AdministratorsCreate->value)
                ->name('admin.administrators.create');
            Route::post('/administrators', [AdminAdministratorController::class, 'store'])
                ->middleware('can:'.AdminPermission::AdministratorsCreate->value)
                ->name('admin.administrators.store');
            Route::get('/administrators/security', [AdminAdministratorController::class, 'security'])
                ->name('admin.administrators.security');
            Route::put('/administrators/security', [AdminAdministratorController::class, 'updateSecurity'])
                ->middleware('throttle:10,1')
                ->name('admin.administrators.security.update');
            Route::put('/administrators/{managedAdministrator}', [AdminAdministratorController::class, 'update'])
                ->middleware('can:'.AdminPermission::AdministratorsUpdate->value)
                ->name('admin.administrators.update');
            Route::delete('/administrators/{managedAdministrator}', [AdminAdministratorController::class, 'destroy'])
                ->middleware('can:'.AdminPermission::AdministratorsDelete->value)
                ->name('admin.administrators.destroy');

            Route::get('/agents', [AdminAgentController::class, 'index'])
                ->middleware('can:'.AdminPermission::AgentsView->value)
                ->name('admin.agents.index');
            Route::get('/agents/create', [AdminAgentController::class, 'create'])
                ->middleware('can:'.AdminPermission::UsersCreate->value)
                ->name('admin.agents.create');
            Route::post('/agents', [AdminAgentController::class, 'store'])
                ->middleware('can:'.AdminPermission::UsersCreate->value)
                ->name('admin.agents.store');
            Route::put('/agents/{agent}', [AdminAgentController::class, 'update'])
                ->middleware('can:'.AdminPermission::UsersManage->value)
                ->name('admin.agents.update');
            Route::delete('/agents/{agent}', [AdminAgentController::class, 'destroy'])
                ->middleware('can:'.AdminPermission::UsersManage->value)
                ->name('admin.agents.destroy');

            Route::match(['get', 'post'], '/operation-plugins/{tool}', [AdminOperationPluginController::class, 'show'])
                ->where('tool', 'monitor|batch-query|batch-freeze|batch-unfreeze|batch-delete')
                ->name('admin.operation-plugins.show');

            Route::get('/settings/system', [AdminSystemSettingController::class, 'index'])
                ->middleware('can:'.AdminPermission::SettingsView->value)
                ->name('admin.settings.system.index');
            Route::put('/settings/system', [AdminSystemSettingController::class, 'update'])
                ->middleware('can:'.AdminPermission::SettingsAppearanceUpdate->value)
                ->name('admin.settings.system.update');
            Route::put('/settings/system/universal-card', [AdminSystemSettingController::class, 'updateUniversalCard'])
                ->middleware('can:'.AdminPermission::SettingsAppearanceUpdate->value)
                ->name('admin.settings.system.universal-card.update');

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
            Route::get('/applications/{application}/users', [AdminApplicationUserController::class, 'index'])
                ->middleware('can:'.AdminPermission::ApplicationsView->value)
                ->name('admin.applications.users.index');
            Route::post('/applications/{application}/users/bulk-action', [AdminApplicationUserController::class, 'bulkAction'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.applications.users.bulk-action');
            Route::get('/application-users/maintenance', [AdminApplicationUserController::class, 'maintenance'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.application-users.maintenance.index');
            Route::post('/application-users/maintenance', [AdminApplicationUserController::class, 'runMaintenance'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.application-users.maintenance.run');
            Route::get('/quick-operations', [AdminQuickOperationController::class, 'index'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.quick-operations.index');
            Route::post('/quick-operations/search', [AdminQuickOperationController::class, 'search'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.quick-operations.search');
            Route::post('/quick-operations/action', [AdminQuickOperationController::class, 'action'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.quick-operations.action');
            Route::put('/applications/{application}/card-users/{card}', [AdminApplicationUserController::class, 'updateCard'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.applications.card-users.update');
            Route::put('/applications/{application}/account-users/{account}', [AdminApplicationUserController::class, 'updateAccount'])
                ->middleware('can:'.AdminPermission::AppAccountsManage->value)
                ->name('admin.applications.account-users.update');
            Route::get('/applications/{application}/cards/make', [AdminCardController::class, 'make'])
                ->middleware('can:'.AdminPermission::BatchesCreate->value)
                ->name('admin.software-card-making.show');
            Route::get('/applications/{application}/card-types', [AdminCardController::class, 'types'])
                ->middleware('can:'.AdminPermission::CardTypesManage->value)
                ->name('admin.software-card-types.index');
            Route::put('/applications/{application}', [AdminApplicationController::class, 'update'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.applications.update');
            Route::post('/applications/{application}/proxy-link', [AdminProxyLinkController::class, 'update'])
                ->middleware('can:'.AdminPermission::ApplicationsUpdate->value)
                ->name('admin.applications.proxy-link.update');
            Route::post('/applications/{application}/toggle', [AdminApplicationController::class, 'toggle'])
                ->middleware('can:'.AdminPermission::ApplicationsStatusUpdate->value)
                ->name('admin.applications.toggle');
            Route::delete('/applications/{application}', [AdminApplicationController::class, 'destroy'])
                ->middleware('can:'.AdminPermission::ApplicationsDelete->value)
                ->name('admin.applications.destroy');

            Route::get('/cards', [AdminCardController::class, 'index'])
                ->middleware('can:'.AdminPermission::CardsView->value)
                ->name('admin.cards.index');
            Route::post('/cards/bulk-action', [AdminCardController::class, 'bulkAction'])
                ->middleware('can:'.AdminPermission::CardsRevokeAny->value)
                ->name('admin.cards.bulk-action');
            Route::get('/cards/maintenance', [AdminCardController::class, 'maintenance'])
                ->middleware('can:'.AdminPermission::CardsBulkManage->value)
                ->name('admin.cards.maintenance.index');
            Route::post('/cards/maintenance', [AdminCardController::class, 'runMaintenance'])
                ->middleware('can:'.AdminPermission::CardsBulkManage->value)
                ->name('admin.cards.maintenance.run');
            Route::post('/card-types', [AdminCardController::class, 'storeType'])
                ->middleware('can:'.AdminPermission::CardTypesManage->value)
                ->name('admin.card-types.store');
            Route::post('/card-types/quick', [AdminCardController::class, 'quickType'])
                ->middleware('can:'.AdminPermission::CardTypesManage->value)
                ->name('admin.card-types.quick');
            Route::delete('/card-types', [AdminCardController::class, 'deleteTypes'])
                ->middleware('can:'.AdminPermission::CardTypesManage->value)
                ->name('admin.card-types.delete');
            Route::put('/card-types/{cardType}', [AdminCardController::class, 'updateType'])
                ->middleware('can:'.AdminPermission::CardTypesManage->value)
                ->name('admin.card-types.update');
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
