@extends('admin.layout')

@section('title', $title)

@section('content')
@php
    $isCardMakingLog = $logType === 'card-making';
    $isClientRequestLog = $logType === 'client-requests';
@endphp
<form id="system-log-maintenance-form" method="post" action="{{ route('admin.system-logs.maintenance', ['adminPath' => $adminPath, 'type' => $logType]) }}">
    @csrf
</form>
<x-admin.table-shell :title="$title" :subtitle="$isClientRequestLog ? '仅在系统设置开启诊断后记录 login.ic' : ($isCardMakingLog ? null : '系统审计数据查询')" panel-class="bsphp-log-panel">
    <x-slot:search><x-admin.filter-bar button-label="⌕" :icon-only="true">
        <x-slot:field><select aria-label="搜索字段"><option>{{ $isClientRequestLog ? '设备 / 卡密指纹 / 错误码 / IP' : ($isCardMakingLog ? '用户' : '用户 / IP / 信息') }}</option></select></x-slot:field>
        <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="{{ $isClientRequestLog ? '输入设备或错误码' : '请输入' }}"></x-slot:keyword>
        <x-slot:sort><select name="sort"><option value="latest" @selected(request('sort','latest')==='latest')>正序</option><option value="oldest" @selected(request('sort')==='oldest')>倒序</option></select></x-slot:sort>
    </x-admin.filter-bar></x-slot:search>
    <x-slot:toolbar>
        <div>
            <button type="submit" form="system-log-maintenance-form" name="action" value="delete_selected" onclick="return confirm('确定删除选中的日志吗？')">删除选定</button>
            <button type="submit" form="system-log-maintenance-form" name="action" value="delete_older_7" onclick="return confirm('确定删除 7 天前的日志吗？')">维护删除最近7天前记录</button>
            <button type="submit" form="system-log-maintenance-form" name="action" value="delete_older_50" onclick="return confirm('确定删除 50 天前的日志吗？')">维护删除最近50天前记录</button>
            <button type="submit" form="system-log-maintenance-form" name="action" value="clear" onclick="return confirm('确定清空当前类型的全部日志吗？')">维护清空记录</button>
        </div>
        <div class="admin-table-utility-tools">
            <button type="button" data-table-density-toggle aria-label="切换表格密度" title="切换表格密度"><span aria-hidden="true">▥</span></button>
            <button type="button" data-table-export aria-label="导出当前表格" title="导出当前表格"><span aria-hidden="true">↥</span></button>
            <button type="button" onclick="window.print()" aria-label="打印" title="打印"><span aria-hidden="true">▤</span></button>
        </div>
    </x-slot:toolbar>
        <table class="{{ $isClientRequestLog ? 'client-trace-table' : '' }}">
            @if($isClientRequestLog)
            <thead><tr><th class="admin-select-column"><input type="checkbox" aria-label="全选" data-card-select-all></th><th>时间 / IP</th><th>软件 / 卡密</th><th>设备对比</th><th>解密请求</th><th>实际返回</th><th>结果</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                @php
                    $traceRequest = is_array($log->before_data) ? $log->before_data : [];
                    $traceResponse = is_array($log->after_data) ? $log->after_data : [];
                    $requestDevice = (string) data_get($traceRequest, 'device', '');
                    $boundDevice = (string) data_get($traceResponse, 'bound_device', '');
                    $returnedDevice = (string) data_get($traceResponse, 'returned_device', '');
                    $deviceMatches = data_get($traceResponse, 'request_device_matches_binding');
                    $deviceBlocked = data_get($traceResponse, 'device_security_state.actively_blocked') === true;
                @endphp
                <tr>
                    <td class="admin-select-column"><input type="checkbox" name="log_ids[]" value="{{ $log->id }}" form="system-log-maintenance-form" aria-label="选择日志 {{ $log->id }}" data-card-select></td>
                    <td><strong>{{ $log->created_at?->format('m-d H:i:s') }}</strong><small class="client-trace-meta">{{ $log->ip ?: '—' }} · #{{ $log->id }}</small></td>
                    <td><strong>{{ data_get($traceRequest, 'application.name', '—') }}</strong><small class="client-trace-meta">{{ data_get($traceRequest, 'credential_masked', '—') }} · {{ data_get($traceRequest, 'credential_fingerprint', '—') }}</small></td>
                    <td class="client-trace-device">
                        <span><b>请求</b><code>{{ $requestDevice ?: '—' }}</code></span>
                        <span><b>绑定</b><code>{{ $boundDevice ?: '未绑定' }}</code></span>
                        @if($returnedDevice !== '')<span><b>返回</b><code>{{ $returnedDevice }}</code></span>@endif
                        @if($deviceBlocked)<em>黑名单蜜罐 · {{ data_get($traceResponse, 'device_security_state.reason_code', '未标注') }}</em>@elseif($deviceMatches === false)<em>设备不一致</em>@elseif($deviceMatches === true)<em class="is-match">设备一致</em>@endif
                    </td>
                    <td><details class="client-trace-details"><summary>查看请求</summary><pre>{{ json_encode($traceRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                    <td><details class="client-trace-details"><summary>{{ data_get($traceResponse, 'diagnostic_code', data_get($traceResponse, 'business_code', '查看返回')) }} · HTTP {{ data_get($traceResponse, 'http_status', '—') }}</summary><pre>{{ json_encode($traceResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                    <td><span class="log-result {{ $log->result === 'success' ? 'is-success' : 'is-danger' }}">{{ $log->result === 'success' ? '成功' : '拦截/失败' }}</span></td>
                </tr>
            @empty<tr><td class="empty-cell" colspan="7">当前暂无{{ $title }}数据，可先在系统信息设置中开启诊断开关。</td></tr>@endforelse
            </tbody>
            @else
            <thead><tr><th class="admin-select-column"><input type="checkbox" aria-label="全选" data-card-select-all></th><th>编号</th><th>记录时间</th><th>登录ip</th><th>用户名/UID</th><th>信息</th>@unless($isCardMakingLog)<th>结果</th>@endunless</tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr><td class="admin-select-column"><input type="checkbox" name="log_ids[]" value="{{ $log->id }}" form="system-log-maintenance-form" aria-label="选择日志 {{ $log->id }}" data-card-select></td><td>{{ $log->id }}</td><td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td><td>{{ $log->ip ?: '—' }}</td>
                    <td>{{ $log->actorAdministrator?->username ?: ($log->actor_user_id ?: ($log->actor_role_snapshot ?: '系统')) }}</td>
                    <td>
                        @if($isCardMakingLog)
                            @php
                                $quantity = (int) data_get($log->after_data, 'quantity', 0);
                                $operatorId = $log->actor_user_id ?: '系统';
                                $isAgentOperation = $log->actor_role_snapshot === 'agent' || str_starts_with($log->action, 'agent.');
                                $operatorLabel = $isAgentOperation ? '代理UID' : '后台管理员UID';
                                $makingMessage = match ($log->action) {
                                    'card_batch.generated' => "后台制卡成功，后台管理员UID:{$operatorId}，{$quantity}张",
                                    'agent.cards_purchased' => "代理制卡成功，代理UID:{$operatorId}，{$quantity}张",
                                    default => ($log->reason ?: '充值卡维护完成')."，{$operatorLabel}:{$operatorId}",
                                };
                            @endphp
                            {{ $makingMessage }}
                        @else
                            <strong>{{ $log->action }}</strong>{{ $log->reason ? '：'.$log->reason : '' }}
                        @endif
                    </td>
                    @unless($isCardMakingLog)<td><span class="log-result {{ $log->result === 'success' ? 'is-success' : 'is-danger' }}">{{ $log->result === 'success' ? '成功' : '拦截/失败' }}</span></td>@endunless
                </tr>
            @empty<tr><td class="empty-cell" colspan="{{ $isCardMakingLog ? 6 : 7 }}">当前暂无{{ $title }}数据。</td></tr>@endforelse
            </tbody>
            @endif
        </table>
    @if($logs->hasPages())
        <x-slot:footer><div class="pager">{{ $logs->links() }}</div></x-slot:footer>
    @endif
</x-admin.table-shell>
@endsection
