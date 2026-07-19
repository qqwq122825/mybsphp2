@extends('admin.layout')

@section('title', '卡密管理')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">LICENSE CARDS</p>
            <h1>{{ $applicationFilter ? $applicationFilter->name.' · 充值卡列表' : '卡密管理' }}</h1>
            <p>{{ $applicationFilter ? '管理当前软件的充值卡类型、库存和卡密状态。' : '先建立充值卡类型，再进行现卡制作。' }}</p>
        </div>
        @can('cards.issue')
            <a class="button button-primary" href="{{ route('admin.card-issues.index', ['adminPath' => $adminPath]) }}">手动发卡</a>
        @endcan
    </header>

    <section class="metric-grid">
        <article class="metric-card metric-blue"><span>全部卡密</span><strong>{{ $metrics['total'] }}</strong><small>累计生成</small></article>
        <article class="metric-card metric-green"><span>库存</span><strong>{{ $metrics['inventory'] }}</strong><small>尚未发出</small></article>
        <article class="metric-card metric-orange"><span>已发出</span><strong>{{ $metrics['issued'] }}</strong><small>等待首次激活</small></article>
        <article class="metric-card metric-red"><span>已激活</span><strong>{{ $metrics['active'] }}</strong><small>正在使用</small></article>
    </section>

    @if ($generatedCards !== [])
        <section class="secret-panel credential-panel">
            <div>
                <span>GENERATED CREDENTIALS · 明文仅展示本次</span>
                <strong>本批共 {{ count($generatedCards) }} 张卡</strong>
            </div>
            <textarea readonly rows="{{ min(10, count($generatedCards) + 1) }}">@foreach($generatedCards as $credential){{ $credential['code'] }} | {{ $credential['secret'] }}
@endforeach</textarea>
        </section>
    @endif

    <section class="dashboard-panel data-panel">
        <header><div><span>CARD LIST</span><h2>卡密列表</h2></div><em>{{ $cards->total() }} 张</em></header>
        <form class="filter-bar" method="get">
            <select name="application">
                <option value="">全部应用</option>
                @foreach($applications as $application)
                    <option value="{{ $application->id }}" @selected(request('application') == $application->id)>{{ $application->name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">全部状态</option>
                @foreach(['inventory','issued','active','expired','frozen','revoked'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="button button-secondary" type="submit">筛选</button>
        </form>
        @if ($cards->isEmpty())
            <div class="empty-activity"><b>◇</b><strong>暂无卡密</strong><p>建立卡种并生成首个批次。</p></div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>卡号</th><th>应用 / 卡种</th><th>批次</th><th>状态</th><th>发出</th><th>激活/到期</th></tr></thead>
                    <tbody>
                    @foreach($cards as $card)
                        <tr>
                            <td><strong>••••-{{ $card->code_last4 }}</strong><small>{{ $card->public_id }}</small></td>
                            <td><strong>{{ $card->application->name }}</strong><small>{{ $card->cardType->name }}</small></td>
                            <td>{{ $card->batch->batch_no }}</td>
                            <td><span class="status-tag status-{{ $card->status }}">{{ $card->status }}</span></td>
                            <td>{{ $card->issued_at?->format('m-d H:i') ?? '—' }}</td>
                            <td><small>{{ $card->activated_at?->format('m-d H:i') ?? '—' }}<br>{{ $card->expires_at?->format('Y-m-d H:i') ?? '—' }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pager">
                @if ($cards->previousPageUrl())<a href="{{ $cards->previousPageUrl() }}">上一页</a>@endif
                <span>第 {{ $cards->currentPage() }} / {{ $cards->lastPage() }} 页</span>
                @if ($cards->nextPageUrl())<a href="{{ $cards->nextPageUrl() }}">下一页</a>@endif
            </div>
        @endif
    </section>
@endsection
