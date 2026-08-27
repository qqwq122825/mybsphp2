@extends('admin.layout')

@section('title', '封控中心')

@section('content')
@php
    $defaultConfigTab = ($errors->has('device_serial') || $errors->has('ip'))
        ? 'add'
        : (request()->filled('keyword') || request()->filled('level') ? 'ips' : 'add');
@endphp
<div class="software-config-form risk-center-form" data-config-form data-default-config-tab="{{ $defaultConfigTab }}">
    <header class="software-settings-heading">
        <div>
            <span>RISK CONTROL CENTER</span>
            <h1>封控中心</h1>
            <p>全软件名单汇总与手动拉黑。当前 {{ $metrics['blocked_devices'] }} 台设备、{{ $metrics['blocked_ips'] }} 个 IP、{{ $metrics['global_blocks'] }} 条全软件封禁。</p>
        </div>
    </header>

    <nav class="software-config-tabs" role="tablist" aria-label="封控中心分类">
        <button type="button" role="tab" data-config-tab="add" aria-controls="risk-center-add">
            <span>01</span><strong>主动添加封禁</strong><small>设备序列号与 IP</small>
        </button>
        <button type="button" role="tab" data-config-tab="devices" aria-controls="risk-center-devices">
            <span>02</span><strong>设备黑名单</strong><small>{{ $metrics['blocked_devices'] }} 台已拉黑</small>
        </button>
        <button type="button" role="tab" data-config-tab="ips" aria-controls="risk-center-ips">
            <span>03</span><strong>风险 IP 列表</strong><small>{{ $metrics['blocked_ips'] }} 个正在封禁</small>
        </button>
    </nav>

    <div class="admin-form-grid software-config-panel" id="risk-center-add" role="tabpanel" data-config-panel="add">
        <form method="post" action="{{ route('admin.risk-control.global-blocks.store', ['adminPath' => $adminPath]) }}">
            @csrf
            <section class="dashboard-panel form-panel form-panel-wide">
                <div class="panel-body software-basic-rows">
                    <label class="software-basic-row">
                        <strong>设备序列号</strong>
                        <input name="device_serial" value="{{ old('device_serial') }}" placeholder="例如 D2QDT96KLH" maxlength="160">
                        <small>按特征码全软件拉黑；可与 IP 同时填写。</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>IP 地址</strong>
                        <input name="ip" value="{{ old('ip') }}" placeholder="可选，可与序列号同时填写">
                        <small>按登录 IP 全软件拉黑，对所有软件立即生效。</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>备注</strong>
                        <input name="note" value="{{ old('note') }}" placeholder="可选" maxlength="500">
                        <small>仅后台可见，便于日后核对拉黑原因。</small>
                    </label>
                </div>
            </section>
            @if($errors->has('device_serial') || $errors->has('ip'))
                <p class="honeypot-global-block-error">{{ $errors->first('device_serial') ?: $errors->first('ip') }}</p>
            @endif
            <div class="form-submit-bar">
                <span>将按全软件范围拉黑所填设备 / IP，保存后立即生效。</span>
                <button class="button button-primary" type="submit" onclick="return confirm('将按全软件范围拉黑所填设备/IP，继续吗？')">全软件拉黑</button>
            </div>
        </form>
    </div>

    <div class="risk-center-panel" data-config-panel="add">
        <section class="dashboard-panel honeypot-device-panel risk-center-block-list">
            <header><div><p class="eyebrow">GLOBAL BLOCKS</p><h2>全软件封禁</h2></div><span>最近 50 条</span></header>
            <div class="honeypot-global-block-list">
                @forelse($globalBlocks as $globalBlock)
                    <article>
                        <strong>{{ $globalBlock->subject_type === 'device' ? '设备' : 'IP' }}</strong>
                        <code>{{ $globalBlock->subject_value }}</code>
                        <span>{{ $globalBlock->administrator?->username ?: '系统' }} · {{ $globalBlock->created_at?->diffForHumans() }}{{ $globalBlock->note ? ' · '.$globalBlock->note : '' }}</span>
                        <form method="post" action="{{ route('admin.risk-control.global-blocks.destroy', ['adminPath'=>$adminPath, 'globalBlock'=>$globalBlock]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('确定解除该全软件封禁吗？')">解除</button>
                        </form>
                    </article>
                @empty
                    <p class="risk-feed-empty">暂无全软件封禁</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="risk-center-panel" id="risk-center-devices" role="tabpanel" data-config-panel="devices" hidden>
        @if($metrics['blocked_devices'] > 0)
            <section class="system-settings-mode-note is-debug">
                <strong>当前有 {{ $metrics['blocked_devices'] }} 台设备处于黑名单</strong>
                <p>黑名单设备使用新卡密时也会进入隐藏响应，客户端统一显示“卡密不存在”；可在客户端请求日志中按完整卡密查询真实原因。</p>
            </section>
        @endif
        <section class="dashboard-panel honeypot-device-panel">
            <header><div><p class="eyebrow">DEVICE BLOCKLIST</p><h2>设备黑名单</h2></div><span>最近 50 条 · 全软件汇总</span></header>
            <div class="honeypot-device-list">
                @forelse($securityDeviceStates as $deviceState)
                    @php($deviceDetail = $securityDeviceDetails[$deviceState->device_hash] ?? [])
                    @php($deviceIdentifier = $deviceDetail['identifier'] ?? null)
                    @php($loginCredential = $deviceDetail['credential'] ?? null)
                    @php($loginCredentialType = $deviceDetail['credential_type'] ?? '授权来源')
                    @php($blockedIp = $deviceDetail['ip'] ?? null)
                    @php($deviceHashSummary = substr($deviceState->device_hash, 0, 12).'…'.substr($deviceState->device_hash, -8))
                    <article>
                        <div class="honeypot-device-identity">
                            <small>{{ $deviceState->application?->code ? $deviceState->application->code.' · ' : '' }}{{ $deviceIdentifier ? '设备序列号' : '设备指纹' }}</small>
                            <code title="{{ $deviceIdentifier ?: $deviceState->device_hash }}">{{ $deviceIdentifier ?: $deviceHashSummary }}</code>
                            <i>{{ $deviceIdentifier ? '指纹 '.$deviceHashSummary : '绑定序列号待关联' }}</i>
                        </div>
                        <div class="honeypot-device-credential">
                            <small>{{ $loginCredentialType }}</small>
                            <code title="{{ $loginCredential ?: '历史记录未关联' }}">{{ $loginCredential ?: '历史记录未关联' }}</code>
                        </div>
                        <span>登录 IP {{ $blockedIp ?: '待关联' }} · {{ $deviceIdentifier ? '已关联绑定设备' : '摘要记录' }} · {{ $deviceState->reason_code ?: '—' }} · 风险分 {{ $deviceState->risk_score }} · {{ $deviceState->last_event_at?->diffForHumans() }}</span>
                        <form class="honeypot-device-release" method="post" action="{{ route('admin.risk-control.device-states.status', ['adminPath'=>$adminPath,'deviceState'=>$deviceState]) }}">
                            @csrf
                            <select name="status" aria-label="处置等级" data-native-select>
                                <option value="observe" @selected($deviceState->status === 'observe')>观察</option>
                                <option value="quarantined" @selected($deviceState->status === 'quarantined')>重点观察</option>
                                <option value="blocked" @selected($deviceState->status === 'blocked')>拉黑</option>
                            </select>
                            @if($deviceState->status === 'blocked')
                                <select name="release_scope" aria-label="解除范围" data-native-select>
                                    <option value="all">全部恢复</option>
                                    <option value="device_only">仅解除设备</option>
                                    <option value="device_credential">设备 + 卡密/账号</option>
                                    <option value="device_ip">设备 + IP</option>
                                </select>
                            @endif
                            <button type="submit" onclick="return confirm({{ $deviceState->status === 'blocked' ? "'将按选定范围调整处置等级，继续吗？'" : "'确认修改该设备的处置等级？改为拉黑时会同步拉黑关联 IP。'" }})">确认修改</button>
                        </form>
                    </article>
                @empty
                    <p class="risk-feed-empty">暂无设备黑名单</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="risk-center-panel" id="risk-center-ips" role="tabpanel" data-config-panel="ips" hidden>
        <x-admin.table-shell title="风险 IP 列表" subtitle="显示已被拉黑或进入风险档位的 IP，覆盖全部软件" panel-class="risk-table-panel">
            <x-slot:search>
                <form class="risk-filter" method="get">
                    <label class="risk-filter-keyword"><span>IP 地址</span><input name="keyword" value="{{ request('keyword') }}" placeholder="输入完整或部分 IP"></label>
                    <button class="button button-primary" type="submit">筛选</button>
                    <a href="{{ route('admin.risk-control.center', ['adminPath'=>$adminPath]) }}#ips">重置</a>
                </form>
            </x-slot:search>
            <table>
                <thead><tr><th>软件</th><th>风险等级</th><th>IP 地址</th><th>窗口请求</th><th>累计请求</th><th>最近接口</th><th>最后出现</th><th>封禁状态</th><th class="risk-actions-column">处置</th></tr></thead>
                <tbody>
                @forelse($states as $state)
                    <tr>
                        <td>{{ $state->application?->code ?: '—' }}</td>
                        <td><span class="risk-level-pill is-{{ $state->level }}"><i></i>{{ $state->level === 'high' ? '高级' : ($state->level === 'low' ? '低级' : '普通') }}</span></td>
                        <td><strong class="risk-ip">{{ $state->ip }}</strong>@if($state->note)<small class="risk-note">{{ $state->note }}</small>@endif</td>
                        <td><strong>{{ $state->window_attempts }}</strong> 次</td><td>{{ number_format($state->total_attempts) }}</td>
                        <td><code>{{ $state->last_endpoint ?: '—' }}</code></td><td>{{ $state->last_seen_at?->format('m-d H:i:s') ?: '—' }}</td>
                        <td>
                            @if($state->permanently_blocked)<span class="risk-block-status is-blocked">长期封禁</span>
                            @elseif($state->blocked_until?->isFuture())<span class="risk-block-status is-blocked">至 {{ $state->blocked_until->format('m-d H:i') }}</span>
                            @else<span class="risk-block-status">观察中</span>@endif
                        </td>
                        <td>
                            <div class="risk-action-menu">
                                <button class="risk-action-trigger" type="button" data-risk-action-trigger aria-haspopup="menu" aria-expanded="false">处置 <span>⌄</span></button>
                                <div class="risk-action-popover" data-risk-action-popover role="menu" hidden>
                                @foreach(['reset'=>'解除并清零','set_low'=>'调整为低级','block_1h'=>'封禁 1 小时','block_24h'=>'封禁 24 小时','block_permanent'=>'长期封禁'] as $action => $label)
                                    <form method="post" action="{{ route('admin.risk-control.states.action', ['adminPath'=>$adminPath,'state'=>$state]) }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><button type="submit">{{ $label }}</button></form>
                                @endforeach
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty-cell" colspan="9">当前筛选范围内没有风险 IP。</td></tr>
                @endforelse
                </tbody>
            </table>
            @if($states->hasPages())
                <x-slot:footer>
                    <div class="pager">{{ $states->links() }}</div>
                </x-slot:footer>
            @endif
        </x-admin.table-shell>
    </div>
</div>
@endsection
