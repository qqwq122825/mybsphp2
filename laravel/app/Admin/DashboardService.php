<?php

namespace App\Admin;

use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\AuditLog;
use App\Models\Card;
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

    /** @return array<string, int|float> */
    public function businessMetrics(): array
    {
        $onlineCutoff = now()->subMinutes(30);

        return [
            'account_total' => ApplicationAccount::query()->count() + Card::query()->whereNotNull('activated_at')->count(),
            'today_login' => ApplicationAccount::query()->whereDate('last_login_at', today())->count()
                + Card::query()->whereDate('last_online_at', today())->count(),
            'agent_total' => 0,
            'today_registered' => ApplicationAccount::query()->whereDate('created_at', today())->count()
                + Card::query()->whereDate('activated_at', today())->count(),
            'balance_yuan' => round(ApplicationAccount::query()->sum('balance_cents') / 100, 2),
            'today_activated' => Card::query()->whereDate('activated_at', today())->count(),
            'online_users' => ApplicationAccount::query()->where('last_login_at', '>=', $onlineCutoff)->count()
                + Card::query()->where('last_online_at', '>=', $onlineCutoff)->count(),
            'software_total' => Application::query()->count(),
            'cards_total' => Card::query()->count(),
            'cards_unused' => Card::query()->whereIn('status', ['inventory', 'issued'])->count(),
            'cards_activated' => Card::query()->whereNotNull('activated_at')->count(),
            'today_cards' => Card::query()->whereDate('activated_at', today())->count(),
            'today_consumption' => 0,
            'today_recharge' => 0,
            'security_today' => AuditLog::query()->whereDate('created_at', today())->where('result', '!=', 'success')->count(),
            'security_week' => AuditLog::query()->where('created_at', '>=', now()->subDays(7))->where('result', '!=', 'success')->count(),
            'api_today' => AuditLog::query()->whereDate('created_at', today())->where('action', 'like', 'api.%')->where('result', '!=', 'success')->count(),
            'api_week' => AuditLog::query()->where('created_at', '>=', now()->subDays(7))->where('action', 'like', 'api.%')->where('result', '!=', 'success')->count(),
        ];
    }

    public function applications()
    {
        return Application::query()
            ->withCount([
                'accounts',
                'cards',
                'cards as activated_cards_count' => fn ($query) => $query->whereNotNull('activated_at'),
                'cards as inventory_cards_count' => fn ($query) => $query->whereIn('status', ['inventory', 'issued']),
            ])
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }
}
