@extends('admin.layout')

@section('title', $title)

@section('content')
@php
    $isCardMakingLog = $logType === 'card-making';
    $isClientRequestLog = $logType === 'client-requests';
    $isClientSimpleLog = $isClientRequestLog && $clientLogMode === 'simple';
@endphp
<form id="system-log-maintenance-form" method="post" action="{{ route('admin.system-logs.maintenance', ['adminPath' => $adminPath, 'type' => $logType]) }}">
    @csrf
    @if($isClientRequestLog)
        <input type="hidden" name="view" value="{{ $clientLogMode }}">
    @endif
</form>
<x-admin.table-shell :title="$title" :subtitle="$isClientRequestLog ? ($isClientSimpleLog ? '日常自动记录；普通卡密错误与授权到期不进入此列表' : '仅在系统设置开启诊断后记录 login.ic') : ($isCardMakingLog ? null : '系统审计数据查询')" panel-class="bsphp-log-panel">
    <x-slot:search><x-admin.filter-bar button-label="⌕" :icon-only="true">
        @if($isClientRequestLog)
            <x-slot:hidden>
                <input type="hidden" name="view" value="{{ $clientLogMode }}">
            </x-slot:hidden>
        @endif
        <x-slot:field><select aria-label="搜索字段"><option>{{ $isClientRequestLog ? '卡密 / 设备 / 错误码 / IP' : ($isCardMakingLog ? '用户' : '用户 / IP / 信息') }}</option></select></x-slot:field>
        <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="{{ $isClientRequestLog ? '输入完整卡密、设备或错误码' : '请输入' }}"></x-slot:keyword>
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
            @if($isClientSimpleLog)
            <thead><tr><th class="admin-select-column"><input type="checkbox" aria-label="全选" data-card-select-all></th><th>时间 / IP</th><th>软件 / 卡密</th><th>真实原因</th><th>客户端提示</th><th>设备</th><th>详细请求</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                @php
                    $simpleRequest = is_array($log->before_data) ? $log->before_data : [];
                    $simpleResult = is_array($log->after_data) ? $log->after_data : [];
                    $detailLog = $clientTraceDetails->get($log->request_id) ?? $log;
                    $detailRequest = is_array($detailLog->before_data) ? $detailLog->before_data : $simpleRequest;
                    $detailResponse = is_array($detailLog->after_data) ? $detailLog->after_data : $simpleResult;
                    $requestDevice = (string) data_get($detailRequest, 'device', data_get($simpleRequest, 'device', ''));
                    $boundDevice = (string) data_get($detailResponse, 'bound_device', data_get($detailResponse, 'card_state.bound_device', ''));
                    $returnedDevice = (string) data_get($detailResponse, 'returned_device', '');
                    $responseCode = (string) data_get($detailResponse, 'diagnostic_code', data_get($detailResponse, 'business_code', data_get($simpleResult, 'internal_code', '未标注')));
                    $httpStatus = (string) data_get($detailResponse, 'http_status', data_get($simpleResult, 'http_status', '—'));
                    $requestPayload = base64_encode(json_encode($detailRequest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
                    $responsePayload = base64_encode(json_encode($detailResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
                @endphp
                <tr>
                    <td class="admin-select-column"><input type="checkbox" name="log_ids[]" value="{{ $log->id }}" form="system-log-maintenance-form" aria-label="选择日志 {{ $log->id }}" data-card-select></td>
                    <td><strong>{{ $log->created_at?->format('m-d H:i:s') }}</strong><small class="client-trace-meta">{{ $log->ip ?: '—' }} · #{{ $log->id }}</small></td>
                    <td><strong>{{ data_get($simpleRequest, 'application.name', '—') }}</strong><small class="client-trace-meta">{{ data_get($simpleRequest, 'credential_masked', '—') }}</small></td>
                    <td><strong>{{ data_get($simpleResult, 'internal_code', '未标注') }}</strong><small class="client-trace-meta">{{ data_get($simpleResult, 'internal_message', '未记录') }}</small></td>
                    <td><span class="log-result is-danger">{{ data_get($simpleResult, 'public_message', '卡密不存在') }}</span></td>
                    <td><code>{{ data_get($simpleRequest, 'device', '—') ?: '—' }}</code></td>
                    <td><button type="button"
                                class="client-trace-detail-button"
                                data-client-trace-detail
                                data-log-id="{{ $detailLog->id }}"
                                data-created-at="{{ $detailLog->created_at?->format('Y-m-d H:i:s') }}"
                                data-ip="{{ $detailLog->ip ?: '—' }}"
                                data-application="{{ data_get($detailRequest, 'application.name', data_get($simpleRequest, 'application.name', '—')) }}"
                                data-credential="{{ data_get($detailRequest, 'credential_masked', data_get($simpleRequest, 'credential_masked', '—')) }}"
                                data-request-device="{{ $requestDevice ?: '—' }}"
                                data-bound-device="{{ $boundDevice ?: '未绑定' }}"
                                data-returned-device="{{ $returnedDevice ?: '—' }}"
                                data-response-code="{{ $responseCode }}"
                                data-http-status="{{ $httpStatus }}"
                                data-result="{{ $detailLog->result === 'success' ? '成功' : '拦截/失败' }}"
                                data-result-tone="{{ $detailLog->result === 'success' ? 'success' : 'danger' }}"
                                data-request-payload="{{ $requestPayload }}"
                                data-response-payload="{{ $responsePayload }}">详细请求 <span aria-hidden="true">→</span></button></td>
                </tr>
            @empty<tr><td class="empty-cell" colspan="7">当前没有需要客服介入的简要诊断记录。</td></tr>@endforelse
            </tbody>
            @elseif($isClientRequestLog)
            <thead><tr><th class="admin-select-column"><input type="checkbox" aria-label="全选" data-card-select-all></th><th>时间 / IP</th><th>软件 / 卡密</th><th>设备对比</th><th>响应摘要</th><th>结果</th><th>详情</th></tr></thead>
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
                    $responseCode = (string) data_get($traceResponse, 'diagnostic_code', data_get($traceResponse, 'business_code', '未标注'));
                    $httpStatus = (string) data_get($traceResponse, 'http_status', '—');
                    $requestPayload = base64_encode(json_encode($traceRequest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
                    $responsePayload = base64_encode(json_encode($traceResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
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
                    <td class="client-trace-response"><strong>{{ $responseCode }}</strong><small>HTTP {{ $httpStatus }}</small></td>
                    <td><span class="log-result {{ $log->result === 'success' ? 'is-success' : 'is-danger' }}">{{ $log->result === 'success' ? '成功' : '拦截/失败' }}</span></td>
                    <td><button type="button"
                                class="client-trace-detail-button"
                                data-client-trace-detail
                                data-log-id="{{ $log->id }}"
                                data-created-at="{{ $log->created_at?->format('Y-m-d H:i:s') }}"
                                data-ip="{{ $log->ip ?: '—' }}"
                                data-application="{{ data_get($traceRequest, 'application.name', '—') }}"
                                data-credential="{{ data_get($traceRequest, 'credential_masked', '—') }}"
                                data-request-device="{{ $requestDevice ?: '—' }}"
                                data-bound-device="{{ $boundDevice ?: '未绑定' }}"
                                data-returned-device="{{ $returnedDevice ?: '—' }}"
                                data-response-code="{{ $responseCode }}"
                                data-http-status="{{ $httpStatus }}"
                                data-result="{{ $log->result === 'success' ? '成功' : '拦截/失败' }}"
                                data-result-tone="{{ $log->result === 'success' ? 'success' : 'danger' }}"
                                data-request-payload="{{ $requestPayload }}"
                                data-response-payload="{{ $responsePayload }}">查看详情 <span aria-hidden="true">→</span></button></td>
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

@if($isClientRequestLog)
<x-admin.window-dialog title="客户端请求详情" eyebrow="LOGIN.IC DIAGNOSTIC" :workspace="false" class="client-trace-dialog" data-client-trace-dialog>
    <div class="client-trace-dialog-body">
        <section class="client-trace-dialog-summary">
            <div><small>日志编号</small><strong data-trace-value="log-id">—</strong></div>
            <div><small>请求时间</small><strong data-trace-value="created-at">—</strong></div>
            <div><small>来源 IP</small><strong data-trace-value="ip">—</strong></div>
            <div><small>应用 / 卡密</small><strong><span data-trace-value="application">—</span><i data-trace-value="credential">—</i></strong></div>
            <div><small>响应</small><strong><span data-trace-value="response-code">—</span><i>HTTP <span data-trace-value="http-status">—</span></i></strong></div>
            <div><small>处理结果</small><strong class="client-trace-dialog-result" data-trace-result>—</strong></div>
        </section>

        <section class="client-trace-dialog-devices" aria-label="设备对比">
            <header><div><small>DEVICE COMPARISON</small><h4>设备对比</h4></div><p>一次对照请求、卡密绑定和服务器实际返回的设备值。</p></header>
            <div>
                <article><small>请求设备</small><code data-trace-value="request-device">—</code></article>
                <article><small>绑定设备</small><code data-trace-value="bound-device">—</code></article>
                <article><small>返回设备</small><code data-trace-value="returned-device">—</code></article>
            </div>
        </section>

        <section class="client-trace-dialog-payloads">
            <article>
                <header><div><small>DECRYPTED REQUEST</small><h4>解密请求</h4></div><button type="button" data-trace-copy="request">复制</button></header>
                <pre data-trace-payload="request">{}</pre>
            </article>
            <article>
                <header><div><small>ACTUAL RESPONSE</small><h4>实际返回</h4></div><button type="button" data-trace-copy="response">复制</button></header>
                <pre data-trace-payload="response">{}</pre>
            </article>
        </section>
    </div>
</x-admin.window-dialog>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-client-trace-dialog]');
    if (!(dialog instanceof HTMLDialogElement)) return;

    const decodePayload = (encoded) => {
        try {
            const bytes = Uint8Array.from(atob(encoded || ''), (character) => character.charCodeAt(0));
            return JSON.stringify(JSON.parse(new TextDecoder().decode(bytes)), null, 2);
        } catch (error) {
            return '{}';
        }
    };

    document.querySelectorAll('[data-client-trace-detail]').forEach((button) => {
        button.addEventListener('click', () => {
            ['logId', 'createdAt', 'ip', 'application', 'credential', 'requestDevice', 'boundDevice', 'returnedDevice', 'responseCode', 'httpStatus'].forEach((key) => {
                const target = dialog.querySelector(`[data-trace-value="${key.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}"]`);
                if (target instanceof HTMLElement) target.textContent = button.dataset[key] || '—';
            });
            const result = dialog.querySelector('[data-trace-result]');
            if (result instanceof HTMLElement) {
                result.textContent = button.dataset.result || '—';
                result.classList.toggle('is-success', button.dataset.resultTone === 'success');
                result.classList.toggle('is-danger', button.dataset.resultTone === 'danger');
            }
            const request = dialog.querySelector('[data-trace-payload="request"]');
            const response = dialog.querySelector('[data-trace-payload="response"]');
            if (request instanceof HTMLElement) request.textContent = decodePayload(button.dataset.requestPayload);
            if (response instanceof HTMLElement) response.textContent = decodePayload(button.dataset.responsePayload);
            dialog.dispatchEvent(new CustomEvent('admin-window:open', { bubbles: true }));
        });
    });

    dialog.querySelectorAll('[data-trace-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const payload = dialog.querySelector(`[data-trace-payload="${button.dataset.traceCopy}"]`);
            if (!(payload instanceof HTMLElement)) return;
            await navigator.clipboard.writeText(payload.textContent || '');
            button.textContent = '已复制';
            window.setTimeout(() => { button.textContent = '复制'; }, 1200);
        });
    });
});
</script>
@endif
@endsection
