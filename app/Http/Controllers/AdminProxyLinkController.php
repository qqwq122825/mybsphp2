<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Models\Application;
use App\Proxy\ProxyLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminProxyLinkController extends Controller
{
    public function update(Request $request, string $adminPath, Application $application, ProxyLinkService $service, AuditLogger $audit): RedirectResponse
    {
        $input = $request->validate([
            'enabled' => ['required', 'boolean'],
            'permission' => ['sometimes', 'in:record,replay,both'],
            'action' => ['sometimes', 'in:save,rotate_path,rotate_path_now,rotate_token'],
        ]);
        $input['action'] ??= 'save';
        DB::transaction(function () use ($request, $application, $service, $audit, $input): void {
            $link = $service->ensure($application);
            $link = $link->newQuery()->whereKey($link->id)->lockForUpdate()->firstOrFail();
            $before = $link->only(['enabled', 'permission']);
            $changes = ['enabled' => (bool) $input['enabled'], 'permission' => $input['permission'] ?? $link->permission];
            if (in_array($input['action'], ['rotate_path', 'rotate_path_now'], true)) {
                $changes += [
                    'previous_path' => $input['action'] === 'rotate_path' ? $link->path : null,
                    'previous_path_until' => $input['action'] === 'rotate_path' ? now()->addMinutes(10) : null,
                    'path' => 'lk_'.bin2hex(random_bytes(16)),
                ];
            }
            if ($input['action'] === 'rotate_token') {
                $changes += [
                    'token_ciphertext' => bin2hex(random_bytes(32)),
                    'node_id' => null, 'node_name' => null, 'last_sync_at' => null,
                    'previous_path' => null, 'previous_path_until' => null,
                ];
            }
            $link->forceFill($changes)->save();
            $audit->record(new AuditEvent(
                action: 'proxy_link.'.$input['action'], targetType: 'application',
                targetId: $application->public_id, applicationId: $application->id,
                before: $before, after: $link->only(['enabled', 'permission']),
                actorRole: $request->user('admin')?->role,
            ), $request);
        });

        return redirect()->route('admin.applications.edit', [
            'adminPath' => $adminPath, 'application' => $application, 'config_tab' => 'proxy',
        ])->with('success', match ($input['action']) {
            'rotate_path' => '地址已更新，旧地址保留 10 分钟。请同步更新 CDN 白名单及 DFMProxy。',
            'rotate_path_now' => '地址已更新，旧地址立即失效。请同步更新 CDN 白名单及 DFMProxy。',
            'rotate_token' => '密钥已重置，原节点绑定已解除。请在 DFMProxy 更新密钥并重新接入。',
            default => '代理联动配置已保存。',
        });
    }
}
