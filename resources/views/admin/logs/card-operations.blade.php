@extends('admin.layout')
@section('title', '卡操作日志')
@push('head')
    @vite('resources/js/admin-ui.js')
@endpush
@section('content')
<div data-element-list data-list-kind="card-operations">
    <x-admin.table-shell title="卡操作日志" subtitle="逐卡记录操作账号、角色、时间及前后状态，只读留存。卡解冻与设备解封是独立操作；历史记录保留原始信息。">
        <x-slot:search>
            <x-admin.filter-bar button-label="⌕" :icon-only="true">
                <x-slot:field><select name="search_field" aria-label="搜索字段">
                    @foreach(['credential'=>'完整卡号 / 卡标识', 'card_id'=>'卡编号', 'actor'=>'操作账号', 'operation'=>'操作内容'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('search_field', 'credential') === $value)>{{ $label }}</option>
                    @endforeach
                </select></x-slot:field>
                <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="输入卡号、卡编号、账号或冻结/解冻"></x-slot:keyword>
                <x-slot:sort><select name="sort"><option value="latest" @selected(request('sort') !== 'oldest')>最新在前</option><option value="oldest" @selected(request('sort') === 'oldest')>最早在前</option></select></x-slot:sort>
            </x-admin.filter-bar>
        </x-slot:search>
        <x-slot:toolbar><span>共 {{ $logs->total() }} 条记录 · 只读留存</span><x-admin.table-tools /></x-slot:toolbar>
        <table>
            <thead><tr><th>时间 / IP</th><th>软件 / 卡密</th><th>操作账号 / 角色</th><th>操作内容</th><th>操作前</th><th>操作后</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td><strong>{{ $row['log']->created_at?->format('Y-m-d H:i:s') }}</strong><small class="client-trace-meta">{{ $row['log']->ip ?: '—' }} · #{{ $row['log']->id }}</small></td>
                    <td><strong>{{ $row['application'] }}</strong><small class="client-trace-meta">卡编号 {{ $row['card_id'] ?? '—' }}</small><code>{{ $row['credential'] }}</code></td>
                    <td><strong>{{ $row['actor'] }}</strong><small class="client-trace-meta">{{ $row['role'] }}</small></td>
                    <td>{{ $row['operation'] }}</td>
                    <td>@foreach($row['before'] as $line)<div>{{ $line }}</div>@endforeach</td>
                    <td>@foreach($row['after'] as $line)<div>{{ $line }}</div>@endforeach</td>
                </tr>
            @empty
                <tr><td class="empty-cell" colspan="6">当前没有匹配的卡操作记录。</td></tr>
            @endforelse
            </tbody>
        </table>
        <x-slot:footer><div class="pager" data-page="{{ $logs->currentPage() }}" data-total="{{ $logs->total() }}" data-per-page="{{ $logs->perPage() }}">{{ $logs->links() }}</div></x-slot:footer>
    </x-admin.table-shell>
</div>
@endsection
