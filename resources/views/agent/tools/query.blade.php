@extends('agent.layout')
@section('title', '批量查询')
@section('content')
<section class="agent-panel agent-card-list-panel">
    <header><h1>批量查询</h1></header>
    <form class="agent-card-filter agent-card-filter-simple" method="get" action="{{ route('agent.tools.query') }}">
        <textarea name="keyword" rows="3" placeholder="输入完整卡号或后4位，多个卡密用空格或换行分隔" autofocus>{{ $keyword }}</textarea>
        <button class="agent-search-button" type="submit" aria-label="搜索">⌕</button>
    </form>

    <x-admin.table-frame frame-class="agent-table-frame">
        <x-slot:toolbar>
            <span>{{ $keyword === '' ? '输入卡密后查询' : '共 '.$cards->total().' 张' }}</span>
        </x-slot:toolbar>
        <table class="agent-card-table"><thead><tr><th>编号</th><th>卡号</th><th>软件名称</th><th>卡类</th><th>时间</th><th>锁状态</th><th>激活状态</th></tr></thead><tbody>
        @forelse($cards as $card)
            <tr>
                <td>{{ $card->id }}</td><td><code>{{ $card->code_ciphertext }}</code></td><td>{{ $card->application->name }}</td><td>{{ $card->cardType->name }}</td>
                <td>{{ $card->cardType->duration_value }}{{ ['minute'=>'分钟','hour'=>'小时','day'=>'天','month'=>'月','year'=>'年'][$card->cardType->duration_unit] ?? '' }}</td>
                <td>{{ $card->card_is_frozen ? '冻结' : '正常' }}</td><td>{{ in_array($card->status,['active','expired','consumed']) ? '已使用' : '未使用' }}</td>
            </tr>
        @empty<tr><td colspan="7" class="empty-cell">{{ $keyword === '' ? '请输入卡密后查询。' : '未找到属于当前代理的充值卡。' }}</td></tr>@endforelse
        </tbody></table>
        @if($cards->hasPages())
            <x-slot:footer><div class="pager">{{ $cards->links() }}</div></x-slot:footer>
        @endif
    </x-admin.table-frame>
</section>
@endsection
