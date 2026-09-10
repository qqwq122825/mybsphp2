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
        <table class="agent-card-table"><thead><tr><th class="agent-star-cell"></th><th>编号</th><th>卡号</th><th>软件名称</th><th>卡类</th><th>时间</th><th>锁状态</th><th>激活状态</th><th>操作</th></tr></thead><tbody>
        @forelse($cards as $card)
            <tr>
                <td class="agent-star-cell">
                    <button type="button" class="agent-star{{ $card->agent_starred ? ' is-on' : '' }}" data-agent-star data-star-url="{{ route('agent.cards.action', $card) }}" aria-label="{{ $card->agent_starred ? '取消标记' : '标记' }}" title="标记">{{ $card->agent_starred ? '★' : '☆' }}</button>
                </td>
                <td>{{ $card->id }}</td><td><code>{{ $card->code_ciphertext }}</code></td><td>{{ $card->application->name }}</td><td>{{ $card->cardType->name }}</td>
                <td>{{ $card->cardType->duration_value }}{{ ['minute'=>'分钟','hour'=>'小时','day'=>'天','month'=>'月','year'=>'年'][$card->cardType->duration_unit] ?? '' }}</td>
                @php $activated = in_array($card->status, ['active', 'expired', 'consumed'], true); @endphp
                <td>{{ $card->card_is_frozen ? '冻结' : '正常' }}</td>
                <td><span class="status-tag {{ $activated ? 'status-active' : 'status-issued' }}">{{ $activated ? '已激活' : '未激活' }}</span></td>
                <td class="agent-row-actions">@include('agent.partials.card-row-actions', ['card' => $card, 'agent' => $agent])</td>
            </tr>
        @empty<tr><td colspan="9" class="empty-cell">暂无代理充值卡。</td></tr>@endforelse
        </tbody></table>
        @if($cards->hasPages())
            <x-slot:footer><div class="pager">{{ $cards->links() }}</div></x-slot:footer>
        @endif
    </x-admin.table-frame>
</section>
@include('agent.partials.action-modals')
<span hidden data-agent-pagination data-page="{{ $cards->currentPage() }}" data-total="{{ $cards->total() }}" data-per-page="{{ $cards->perPage() }}"></span>
@endsection
@push('scripts')
<script>(()=>{
    const token=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
    const paint=(button,on)=>{button.classList.toggle('is-on',on);button.textContent=on?'★':'☆';button.setAttribute('aria-label',on?'取消标记':'标记');};
    document.querySelectorAll('[data-agent-star]').forEach((button)=>{
        let seq=0;
        button.addEventListener('click',async()=>{
            const mine=++seq;
            const on=!button.classList.contains('is-on');
            paint(button,on);
            try{
                const response=await fetch(button.getAttribute('data-star-url')||'',{
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'},
                    body:JSON.stringify({action:'star'}),
                    credentials:'same-origin'
                });
                if(!response.ok)throw new Error();
                const data=await response.json();
                if(mine!==seq)return;
                paint(button,!!data.starred);
            }catch{
                if(mine!==seq)return;
                paint(button,!on);
            }
        });
    });
    document.querySelectorAll('[data-agent-modal-close]').forEach((button)=>button.addEventListener('click',()=>button.closest('.agent-modal')?.remove()));
})();</script>
@endpush
