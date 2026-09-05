@extends('agent.layout')
@section('title', '卡管理')
@section('content')
<section class="agent-panel agent-card-list-panel">
    <header><h1>卡列表</h1></header>
    <form class="agent-card-filter agent-card-filter-simple" method="get">
        <input name="keyword" value="{{ request('keyword') }}" placeholder="搜索完整卡号或后4位">
        <button class="agent-search-button" type="submit" aria-label="搜索">⌕</button>
    </form>

    <x-admin.table-frame frame-class="agent-table-frame">
        <x-slot:toolbar>
            <div><a class="button" href="{{ route('agent.cards.purchase') }}">余额制卡</a></div>
            <span>共 {{ $cards->total() }} 张</span>
        </x-slot:toolbar>
        <table class="agent-card-table"><thead><tr><th>编号</th><th>卡号</th><th>软件名称</th><th>卡类</th><th>时间</th><th>锁状态</th><th>激活状态</th><th>操作</th></tr></thead><tbody>
        @forelse($cards as $card)
            <tr>
                <td>{{ $card->id }}</td><td><code>{{ $card->code_ciphertext }}</code></td><td>{{ $card->application->name }}</td><td>{{ $card->cardType->name }}</td>
                <td>{{ $card->cardType->duration_value }}{{ ['minute'=>'分钟','hour'=>'小时','day'=>'天','month'=>'月','year'=>'年'][$card->cardType->duration_unit] ?? '' }}</td>
                <td>{{ $card->card_is_frozen ? '冻结' : '正常' }}</td><td>{{ in_array($card->status,['active','expired','consumed']) ? '已使用' : '未使用' }}</td>
                <td class="agent-row-actions">
                    <form method="post" action="{{ route('agent.cards.action', $card) }}">@csrf<input type="hidden" name="action" value="{{ $card->card_is_frozen || $card->user_status === 'frozen' ? 'unfreeze' : 'freeze' }}"><button type="submit">{{ $card->card_is_frozen || $card->user_status === 'frozen' ? '解冻' : '冻结' }}</button></form>
                    <form method="post" action="{{ route('agent.cards.action', $card) }}">@csrf<input type="hidden" name="action" value="unbind"><button type="submit">解绑</button></form>
                </td>
            </tr>
        @empty<tr><td colspan="8" class="empty-cell">暂无代理充值卡。</td></tr>@endforelse
        </tbody></table>
        @if($cards->hasPages())
            <x-slot:footer><div class="pager">{{ $cards->links() }}</div></x-slot:footer>
        @endif
    </x-admin.table-frame>
</section>
@include('agent.partials.action-modals')
@endsection
@push('scripts')
<script>(()=>{document.querySelectorAll('[data-agent-modal-close]').forEach((button)=>button.addEventListener('click',()=>button.closest('.agent-modal')?.remove()));})();</script>
@endpush
