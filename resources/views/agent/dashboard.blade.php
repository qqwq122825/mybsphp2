@extends('agent.layout')
@section('title', '代理中控台')
@section('content')
<section class="agent-panel agent-announcement-panel">
    <header><h1>代理公告</h1></header>
    <div>{{ $announcement ?: '欢迎使用代理商管理平台，业务数据已与授权系统实时同步。' }}</div>
</section>

@php
    $items = [
        ['账户余额', number_format($metrics['balance'] / 100, 2), '元', '元', 'is-balance'],
        ['卡总数', $metrics['total'], '张', '卡', ''],
        ['已经激活使用', $metrics['activated'], '张', '卡', ''],
        ['未激活使用', $metrics['unused'], '张', '卡', ''],
        ['已冻结卡', $metrics['frozen'], '张', '卡', ''],
        ['今天制卡', $metrics['today_created'], '张', '卡', ''],
        ['今天激活', $metrics['today_activated'], '张', '卡', ''],
        ['今天冻结', $metrics['today_frozen'], '张', '卡', ''],
        ['昨天激活', $metrics['yesterday_activated'], '张', '卡', ''],
    ];
@endphp
<section class="agent-metric-grid">
    @foreach($items as [$label, $value, $unit, $badge, $class])
        <article class="agent-metric-card {{ $class }}">
            <header><strong>{{ $label }}</strong><span>{{ $badge }}</span></header>
            <div><b>{{ $value }}</b><small>{{ $unit }}</small></div>
        </article>
    @endforeach
</section>
@endsection
