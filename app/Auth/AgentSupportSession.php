<?php

namespace App\Auth;

use App\Models\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AgentSupportSession
{
    public const KEY = 'agent_support_session';

    public const ATTRIBUTE = 'agent_support_read_only';

    public function start(Request $request, Administrator $actor, Administrator $agent): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, [
            'actor_id' => (int) $actor->getKey(),
            'agent_id' => (int) $agent->getKey(),
            'expires' => now()->addMinutes(15)->getTimestamp(),
        ]);
    }

    public function agent(Request $request): ?Administrator
    {
        $support = $request->session()->get(self::KEY);
        if (! is_array($support) || (int) ($support['expires'] ?? 0) <= now()->getTimestamp()) {
            return null;
        }
        $actor = Auth::guard('admin')->user()?->fresh();
        if (! $actor instanceof Administrator || (int) $actor->getKey() !== (int) ($support['actor_id'] ?? 0)
            || ! $actor->hasRole('super_admin') || ! app(AdminSession::class)->valid($request, $actor)) {
            return null;
        }

        return Administrator::query()->where('role', 'agent')->where('status', 'active')->find($support['agent_id'] ?? null);
    }
}
