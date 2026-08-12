@extends('admin.layout')

@section('title', '诊断编号查询')

@section('content')
<header class="page-heading">
    <div>
        <p class="eyebrow">CLIENT DIAGNOSTIC LOOKUP</p>
        <h1>诊断编号查询</h1>
        <p>根据客户端“卡密不存在XXX”的三位编号查询服务器保留的真实原因。</p>
    </div>
</header>

<section class="system-settings-mode-note is-debug">
    <strong>客服处理方式</strong>
    <p>让用户提供完整编号和出现时间。编号会重复生成，结果较多时结合时间、软件与来源 IP 判断。</p>
</section>

<x-admin.table-shell title="编号查询结果" subtitle="编号范围 201–999；真实原因仅在总后台显示">
    <x-slot:search>
        <form class="risk-filter" method="get">
            <label class="risk-filter-keyword">
                <span>诊断编号</span>
                <input type="number" name="reference" min="201" max="999" value="{{ $reference }}" placeholder="例如 241" required autofocus>
            </label>
            <button class="button button-primary" type="submit">查询</button>
            @if($reference !== null)<a href="{{ route('admin.system-logs.diagnostics', ['adminPath' => $adminPath]) }}">重置</a>@endif
        </form>
    </x-slot:search>

    <table>
        <thead><tr><th>诊断编号</th><th>时间 / IP</th><th>软件</th><th>真实原因</th><th>对外提示</th><th>请求标识</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            @php
                $requestData = is_array($log->before_data) ? $log->before_data : [];
                $resultData = is_array($log->after_data) ? $log->after_data : [];
                $internalCode = (string) data_get($resultData, 'internal_code', '未标注');
                $internalMessage = (string) data_get($resultData, 'internal_message', '未记录');
            @endphp
            <tr>
                <td><strong>{{ data_get($resultData, 'diagnostic_reference', '—') }}</strong></td>
                <td><strong>{{ $log->created_at?->format('Y-m-d H:i:s') }}</strong><small class="client-trace-meta">{{ $log->ip ?: '—' }}</small></td>
                <td><strong>{{ data_get($requestData, 'application.name', '—') }}</strong><small class="client-trace-meta">{{ data_get($requestData, 'application.public_id', '—') }}</small></td>
                <td><strong>{{ $internalCode }}</strong><small class="client-trace-meta">{{ $internalMessage }}</small></td>
                <td><span class="log-result is-danger">{{ data_get($resultData, 'public_message', '卡密不存在') }}</span></td>
                <td><code>{{ $log->request_id }}</code></td>
            </tr>
        @empty
            <tr><td class="empty-cell" colspan="6">{{ $reference === null ? '输入三位诊断编号后查询。' : '当前记录中未找到该编号，请核对编号和出现时间。' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</x-admin.table-shell>
@endsection
