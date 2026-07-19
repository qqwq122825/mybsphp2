<?php

namespace App\Admin;

use App\Models\Administrator;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        return [
            'administrators' => Administrator::query()->where('status', 'active')->count(),
            'logins_today' => AuditLog::query()
                ->where('action', 'admin.login')
                ->whereDate('created_at', today())
                ->count(),
            'failed_logins_today' => AuditLog::query()
                ->where('action', 'admin.login_failed')
                ->whereDate('created_at', today())
                ->count(),
            'operations_today' => AuditLog::query()
                ->whereDate('created_at', today())
                ->count(),
        ];
    }

    /**
     * @return list<AuditLog>
     */
    public function recentActivity(): array
    {
        return AuditLog::query()
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->all();
    }

    public function databaseHealthy(): bool
    {
        return DB::selectOne('SELECT 1 AS healthy') !== null;
    }
}
