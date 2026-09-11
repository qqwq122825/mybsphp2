<?php

namespace Tests\Support;

use App\Models\Administrator;
use App\Models\AuditLog;
use App\Models\GlobalSecurityBlock;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;

/** In-memory presentation fixtures; never persists records or changes permissions. */
final class AdminTablePreview
{
    public static function render(string $page): string
    {
        $role = (new Role)->forceFill(['id' => 1, 'code' => 'super_admin', 'name' => '系统管理员']);
        $role->setRelation('permissions', new Collection);
        $administrator = (new Administrator)->forceFill(['id' => 1, 'username' => 'preview_admin', 'role' => 'super_admin', 'status' => 'active']);
        $administrator->setRelation('roles', new Collection([$role]));
        Auth::shouldUse('admin');
        Auth::guard('admin')->setUser($administrator);
        $path = match ($page) {
            'agent-dashboard' => '/agent/dashboard',
            'agent-login', 'agent-login-error' => '/agent/login',
            'agent-purchase', 'agent-purchase-low', 'agent-purchase-empty', 'agent-purchase-result' => '/agent/purchase',
            'agent-finance', 'agent-finance-empty' => '/agent/finance',
            'agent-cards', 'agent-cards-empty', 'agent-delete', 'agent-unbind' => '/agent/cards',
            'software', 'software-empty' => '/admin/applications',
            'software-create' => '/admin/applications/create',
            'software-config' => '/admin/applications/preview-software-1/edit',
            'software-account-config' => '/admin/applications/preview-software-4/edit',
            'software-users' => '/admin/applications/preview-software-1/users',
            'software-account-users' => '/admin/applications/preview-software-4/users',
            'center', 'empty' => '/admin/risk-control/center',
            'administrators' => '/admin/administrators',
            'detailed' => '/admin/system-logs/client-requests?view=detailed',
            'simple' => '/admin/system-logs/client-requests',
            'protection' => '/admin/system-logs/protection',
            default => '/admin/system-logs/blocklist-requests',
        };
        $request = Request::create('http://127.0.0.1:8092'.$path);
        $request->setLaravelSession(app('session')->driver());
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
        app('url')->setRequest($request);
        $common = ['appName' => '软件授权系统', 'administrator' => $administrator, 'adminPath' => 'admin', 'databaseHealthy' => true, 'errors' => new ViewErrorBag];
        if (str_starts_with($page, 'agent-')) {
            return AgentPreview::render($page, $common, $request);
        }
        if (str_starts_with($page, 'software')) {
            return SoftwarePreview::render($page, $common, $request);
        }
        $at = CarbonImmutable::parse('2026-09-10 17:08:35');
        $devices = ['DX3DRAQ2N741', '3CFDD33C-C703-42F0-9E00-3B36090270EC', 'NKQ2D3YHKW', 'DMPXX07AKD89', 'KWWP4R9G5Y', 'QF9VV04M76'];
        $ips = ['203.0.113.24', '2001:db8:2000:9872:f425:3ba4:a953:4363', '198.51.100.46', '192.0.2.247', '203.0.113.12', '2001:db8:846d:6400:5379:9235:1234:5678'];
        $rows = collect();
        for ($i = 0; $i < 12; $i++) {
            $rows->push(['key' => 'global:'.($i + 1), 'kind' => 'device', 'value' => $devices[$i % 6], 'related' => $i === 1 ? implode('、', array_merge([$ips[1]], $ips, ['203.0.113.28'])) : $ips[$i % 6], 'reason' => $i % 3 === 0 ? '套卡探测 INVALID_CARD 达到 3 次 / 120 秒' : '快速拉黑', 'at' => $at->subMinutes($i * 17)]);
        }
        if (in_array($page, ['center', 'empty'], true)) {
            return view('admin.risk-control.center', $common + ['metrics' => ['events_24h' => $page === 'empty' ? 0 : 8, 'blocked_devices' => $page === 'empty' ? 0 : 11], 'blocks' => new LengthAwarePaginator($page === 'empty' ? [] : $rows, $page === 'empty' ? 0 : 32, 20, 1, ['path' => $request->url()])])->render();
        }
        if ($page === 'administrators') {
            return view('admin.administrators.index', $common + ['administrators' => new LengthAwarePaginator([$administrator], 1, 20), 'roles' => collect([$role])])->render();
        }
        $logs = collect();
        for ($i = 0; $i < 12; $i++) {
            $success = $i === 3 && $page !== 'blocklist';
            $log = (new AuditLog)->forceFill([
                'id' => 4363 - $i, 'public_id' => 'preview-'.$i, 'request_id' => 'preview-request-'.$i,
                'ip' => $i === 6 ? $ips[1] : $ips[$i % 6], 'created_at' => $at->subMinutes($i * 3),
                'action' => $page === 'protection' ? 'admin.login_failed' : 'client.blocklist_hit', 'actor_role_snapshot' => 'client',
                'before_data' => ['application' => ['name' => $i === 2 ? '桌面客户端 · 长名称样式验证' : '桌面客户端'], 'credential' => 'DEMO-CARD-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'device' => $devices[$i % 6]],
                'after_data' => ['internal_code' => $success ? 'OK' : 'DEVICE_BLOCKED', 'internal_message' => $success ? '登录验证通过。' : '设备已进入安全封控。', 'public_message' => $success ? '登录成功' : '卡密不存在', 'http_status' => 200, 'diagnostic_code' => $success ? 'OK' : 'DEVICE_BLOCKED', 'bound_device' => $devices[$i % 6], 'username' => 'preview_user'],
                'result' => $success ? 'success' : 'failed', 'reason' => '密码不正确',
            ]);
            $log->setRelation('actorAdministrator', $administrator);
            $logs->push($log);
        }
        $type = match ($page) {
            'simple', 'detailed' => 'client-requests', 'protection' => 'protection', default => 'blocklist-requests'
        };

        return view('admin.logs.index', $common + [
            'title' => match ($page) {
                'simple', 'detailed' => '客户端请求日志', 'protection' => '系统安全防护日志', default => '黑名单请求日志'
            },
            'logType' => $type, 'clientLogMode' => $page === 'detailed' ? 'detailed' : 'simple',
            'logs' => new LengthAwarePaginator($logs, 128, 20, 1, ['path' => $request->url()]),
            'clientTraceDetails' => collect(), 'cardCodesByPublicId' => collect(), 'cardMakingBatches' => collect(),
            'blockedDeviceHashes' => collect([GlobalSecurityBlock::hashValue($devices[0])]),
        ])->render();
    }
}
