<?php

namespace App\Http\Controllers;

use App\Admin\DashboardService;
use App\Auth\AdminPermission;
use App\Install\InstallationManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(string $adminPath): View
    {
        $canViewAudit = Gate::allows(AdminPermission::AuditView->value);

        return view('admin.dashboard', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => Auth::guard('admin')->user(),
            'metrics' => $this->dashboard->metrics(),
            'recentActivity' => $canViewAudit ? $this->dashboard->recentActivity() : [],
            'canViewAudit' => $canViewAudit,
            'databaseHealthy' => $this->dashboard->databaseHealthy(),
        ]);
    }
}
