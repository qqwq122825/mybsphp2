@extends('agent.layout')
@section('title', '批量查询')
@php
    $statusNames = [
        'inventory' => '未使用', 'issued' => '已发出', 'active' => '已使用',
        'expired' => '已到期', 'frozen' => '已冻结', 'revoked' => '已删除', 'consumed' => '已用完',
    ];
@endphp
@section('content')
<x-admin.table-shell title="批量查询" subtitle="输入卡密后可在表格内冻结、解冻或解绑。已使用卡密冻结时会询问冻结用户或删除卡密；解绑时会询问是否扣除解绑时间。" panel-class="agent-query-panel">
    <x-slot:search>
        <x-admin.filter-bar :action="route('agent.tools.query')" keyword-label="卡密：" button-label="⌕" :icon-only="true">
            <x-slot:keyword>
                <textarea name="keyword" rows="2" placeholder="输入完整卡号或后4位，多个卡密用空格或换行分隔" autofocus>{{ $keyword }}</textarea>
            </x-slot:keyword>
        </x-admin.filter-bar>
    </x-slot:search>
    <x-slot:toolbar>
        <span>{{ $keyword === '' ? '输入卡密后查询' : '共 '.$cards->total().' 张' }}</span>
    </x-slot:toolbar>
    @if($cards->hasPages())
        <x-slot:footer>
            <span>共 {{ $cards->total() }} 张</span>
            <div class="pager">{{ $cards->links() }}</div>
        </x-slot:footer>
    @endif
    <table class="bsphp-card-table">
        <thead>
        <tr>
            <th>编号</th>
            <th>卡号</th>
            <th>软件名称</th>
            <th>卡类</th>
            <th>冻结状态</th>
            <th>充值状态</th>
            <th>制作时间</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        @forelse($cards as $card)
            @php
                $locked = $card->card_is_frozen || $card->user_status === 'frozen';
            @endphp
            <tr>
                <td>{{ $card->id }}</td>
                <td><code>{{ $card->code_ciphertext }}</code></td>
                <td>{{ $card->application->name }}</td>
                <td>{{ $card->cardType->name }}</td>
                <td>{{ $card->user_status === 'frozen' ? '用户已冻结' : ($card->card_is_frozen ? '已冻结' : '正常') }}</td>
                <td><span class="status-tag status-{{ $card->status }}">{{ $statusNames[$card->status] ?? $card->status }}</span></td>
                <td>{{ $card->created_at?->format('Y-m-d H:i:s') }}</td>
                <td class="agent-row-actions">
                    <form method="post" action="{{ route('agent.cards.action', $card) }}">
                        @csrf
                        <input type="hidden" name="action" value="{{ $locked ? 'unfreeze' : 'freeze' }}">
                        <button type="submit">{{ $locked ? '解冻' : '冻结' }}</button>
                    </form>
                    <form method="post" action="{{ route('agent.cards.action', $card) }}">
                        @csrf
                        <input type="hidden" name="action" value="unbind">
                        <button type="submit">解绑</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="bsphp-table-empty">{{ $keyword === '' ? '请输入卡密后查询。' : '未找到属于当前代理的充值卡。' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</x-admin.table-shell>
@include('agent.partials.action-modals')
@endsection
@push('scripts')
<script>(()=>{document.querySelectorAll('[data-agent-modal-close]').forEach((button)=>button.addEventListener('click',()=>button.closest('.agent-modal')?.remove()));})();</script>
@endpush
