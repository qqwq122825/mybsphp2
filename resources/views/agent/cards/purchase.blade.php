@extends('agent.layout')
@section('title', '余额制卡')
@section('content')
<section class="agent-panel agent-form-panel">
    <header><h1>余额购卡</h1></header>
    <div class="agent-form-tip">选择卡类型和数量后即可快速制卡，系统会自动计算当前代理价格。</div>

    <form method="post" action="{{ route('agent.cards.purchase.store') }}" class="agent-business-form" data-agent-purchase-form>
        @csrf
        <label><strong>卡串类型</strong><select name="card_type_id" required data-agent-card-type><option value="">请选择制作类型...</option>@foreach($cardTypes as $type)@php($agentPrice = (int) $agentPrices->get($type->id, $type->default_agent_price_cents))<option value="{{ $type->id }}" data-price="{{ $agentPrice }}" @selected((string)old('card_type_id') === (string)$type->id)>{{ $type->application->name }} &gt; {{ $type->name }}，价格{{ number_format($agentPrice / 100, 2) }}元</option>@endforeach</select><small>已按当前代理购卡折扣计算</small></label>
        <label><strong>制作数量</strong><input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="500" required data-agent-quantity><small>需要制卡数量</small></label>
        <label><strong>备注</strong><input name="note" value="{{ old('note') }}" maxlength="500" placeholder="自己可见"><small>选填</small></label>
        <div class="agent-balance-row"><strong>余额</strong><span>{{ number_format($agent->agent_balance_cents / 100, 2) }} 元</span><small>本次预计扣款：<b data-agent-total>0.00</b> 元</small></div>
        <div class="agent-form-actions"><button type="submit">确认制卡</button></div>
    </form>

    @if($generatedCards !== [])
        <section class="agent-generated-result">
            <header><div><h2>制卡成功</h2><p>{{ $generatedMeta['application'] ?? '' }} · {{ $generatedMeta['card_type'] ?? '' }} · {{ $generatedMeta['quantity'] ?? count($generatedCards) }} 张</p></div><button type="button" data-copy-generated>复制全部</button></header>
            <textarea readonly data-generated-content>@foreach($generatedCards as $credential){{ $credential['code'] }}{{ $credential['secret'] !== '' ? "\t".$credential['secret'] : '' }}@if(!$loop->last){{ "\n" }}@endif @endforeach</textarea>
        </section>
    @endif
</section>
@endsection
@push('scripts')
<script>(()=>{const form=document.querySelector('[data-agent-purchase-form]');if(form){const type=form.querySelector('[data-agent-card-type]'),qty=form.querySelector('[data-agent-quantity]'),total=form.querySelector('[data-agent-total]'),update=()=>{const price=Number(type.selectedOptions[0]?.dataset.price||0),count=Math.max(0,Number(qty.value||0));total.textContent=(price*count/100).toFixed(2)};type.addEventListener('change',update);qty.addEventListener('input',update);update()}const copy=document.querySelector('[data-copy-generated]'),content=document.querySelector('[data-generated-content]');copy?.addEventListener('click',async()=>{await navigator.clipboard.writeText(content.value);copy.textContent='已复制'});})();</script>
@endpush
