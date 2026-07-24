@extends('agent.layout')
@section('title', '卡管理')
@section('content')
<section class="agent-panel agent-card-list-panel">
    <header><h1>卡列表</h1></header>
    <form class="agent-card-filter" method="get">
        <select name="application"><option value="">全部软件</option>@foreach($applications as $application)<option value="{{ $application->id }}" @selected((string)request('application') === (string)$application->id)>{{ $application->name }}</option>@endforeach</select>
        <input name="keyword" value="{{ request('keyword') }}" placeholder="搜索完整卡号或后4位">
        <select name="card_type"><option value="">全部卡类</option>@foreach($cardTypes as $type)<option value="{{ $type->id }}" @selected((string)request('card_type') === (string)$type->id)>{{ $type->name }}</option>@endforeach</select>
        <select name="sort"><option value="oldest" @selected(request('sort') === 'oldest')>正序</option><option value="latest" @selected(request('sort','latest') === 'latest')>倒序</option></select>
        <select name="usage"><option value="">全部使用</option><option value="unused" @selected(request('usage') === 'unused')>未使用</option><option value="used" @selected(request('usage') === 'used')>已使用</option></select>
        <select name="freeze"><option value="">全部状态</option><option value="normal" @selected(request('freeze') === 'normal')>正常</option><option value="frozen" @selected(request('freeze') === 'frozen')>已冻结</option></select>
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
                    <form method="post" action="{{ route('agent.cards.action', $card) }}">@csrf<input type="hidden" name="action" value="{{ $card->card_is_frozen ? 'unfreeze' : 'freeze' }}"><button type="submit">{{ $card->card_is_frozen ? '解冻' : '冻结' }}</button></form>
                </td>
            </tr>
        @empty<tr><td colspan="8" class="empty-cell">暂无代理充值卡。</td></tr>@endforelse
        </tbody></table>
        @if($cards->hasPages())
            <x-slot:footer><div class="pager">{{ $cards->links() }}</div></x-slot:footer>
        @endif
    </x-admin.table-frame>
</section>
@endsection
