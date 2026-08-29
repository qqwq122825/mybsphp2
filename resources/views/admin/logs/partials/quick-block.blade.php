@php
    $blockDevice = trim((string) ($blockDevice ?? ''));
    $blockIp = trim((string) ($blockIp ?? ''));
    $deviceBlocked = $blockDevice !== ''
        && $blockedDeviceHashes->contains(\App\Models\GlobalSecurityBlock::hashValue($blockDevice));
    $ipBlocked = $blockIp !== ''
        && $blockedIpHashes->contains(\App\Models\GlobalSecurityBlock::hashValue($blockIp));
    $hasTarget = $blockDevice !== '' || $blockIp !== '';
    $alreadyBlocked = $hasTarget
        && ($blockDevice === '' || $deviceBlocked)
        && ($blockIp === '' || $ipBlocked);
@endphp
@can('applications.update')
    @if (! $hasTarget)
        —
    @elseif ($alreadyBlocked)
        已封禁
    @else
        <form method="post" action="{{ route('admin.risk-control.global-blocks.store', ['adminPath' => $adminPath]) }}">
            @csrf
            @if ($blockDevice !== '')
                <input type="hidden" name="device_serial" value="{{ $blockDevice }}">
            @endif
            @if ($blockIp !== '')
                <input type="hidden" name="ip" value="{{ $blockIp }}">
            @endif
            <input type="hidden" name="note" value="客户端请求日志快速拉黑">
            <button type="submit" class="client-trace-block-button" onclick="return confirm('确定拉黑该请求的设备和 IP 吗？拉黑后任意软件登录显示卡密不存在。')">快速拉黑</button>
        </form>
    @endif
@else
    —
@endcan
