@php
    $blockDevice = trim((string) ($blockDevice ?? ''));
    $deviceHash = \App\Models\GlobalSecurityBlock::hashValue($blockDevice);
    $hasTarget = $blockDevice !== '';
    $alreadyBlocked = $hasTarget && (
        $blockedDeviceHashes->contains($deviceHash)
        || ($blockedApplicationDeviceKeys ?? collect())->contains($log->application_id.':'.$deviceHash)
    );
@endphp
@can('applications.update')
    @if (! $hasTarget)
        —
    @elseif ($alreadyBlocked)
        <span class="log-result is-muted">已封禁</span>
    @else
        <form method="post" action="{{ route('admin.risk-control.global-blocks.store', ['adminPath' => $adminPath]) }}">
            @csrf
            @if ($blockDevice !== '')
                <input type="hidden" name="device_serial" value="{{ $blockDevice }}">
            @endif
            <input type="hidden" name="source" value="quick">
            <input type="hidden" name="note" value="客户端请求日志快速拉黑">
            <x-admin.table-action tone="danger" type="submit" class="client-trace-block-button" onclick="return confirm('确定拉黑该请求的设备 吗？拉黑后任意软件登录显示卡密不存在。')">快速拉黑</x-admin.table-action>
        </form>
    @endif
@else
    —
@endcan
