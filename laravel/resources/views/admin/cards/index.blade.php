@extends('admin.layout')

@section('title', '卡密管理')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">LICENSE CARDS</p>
            <h1>卡密管理</h1>
            <p>先建立卡种，再生成安全随机卡密批次。</p>
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

    <section class="management-grid" id="card-types">
        @can('card_types.manage')
            <form class="dashboard-panel form-panel" method="post" action="{{ route('admin.card-types.store', ['adminPath' => $adminPath]) }}">
                @csrf
                <header><div><span>CARD TYPE</span><h2>新建卡种</h2></div><em>权益规则</em></header>
                <div class="panel-body stack-form compact-form">
                    <label class="field"><span>所属应用</span>
                        <select name="application_id" required>
                            <option value="">请选择应用</option>
                            @foreach ($applications as $application)
                                <option value="{{ $application->id }}" @selected(old('application_id') == $application->id)>{{ $application->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="field-grid">
                        <label class="field"><span>卡种名称</span><input name="name" value="{{ old('name') }}" placeholder="例如 30 天卡" required></label>
                        <label class="field"><span>卡种代号</span><input name="code" value="{{ old('code') }}" placeholder="month30" required></label>
                    </div>
                    <div class="field-grid">
                        <label class="field"><span>权益类型</span>
                            <select name="benefit_kind">
                                <option value="duration">按时长</option>
                                <option value="points">按点数</option>
                                <option value="fixed_expiry">固定到期</option>
                            </select>
                        </label>
                        <label class="field"><span>时长（天）</span><input type="number" name="duration_days" min="1" max="3650" value="{{ old('duration_days', 30) }}"></label>
                    </div>
                    <div class="field-grid">
                        <label class="field"><span>点数</span><input type="number" name="points_amount" min="1" value="{{ old('points_amount') }}"></label>
                        <label class="field"><span>固定到期时间</span><input type="datetime-local" name="fixed_expires_at" value="{{ old('fixed_expires_at') }}"></label>
                    </div>
                    <div class="field-grid three-columns">
                        <label class="field"><span>面值（元）</span><input type="number" step="0.01" name="face_price" value="{{ old('face_price', 0) }}"></label>
                        <label class="field"><span>卡号前缀</span><input name="code_prefix" value="{{ old('code_prefix', 'BS') }}" required></label>
                        <label class="field"><span>卡号长度</span><input type="number" name="code_length" min="16" max="40" value="{{ old('code_length', 20) }}" required></label>
                    </div>
                    <label class="field"><span>密码长度</span><input type="number" name="secret_length" min="6" max="24" value="{{ old('secret_length', 8) }}" required></label>
                    <button class="button button-primary" type="submit">创建卡种</button>
                </div>
            </form>
        @endcan

        @can('batches.create')
            <form class="dashboard-panel form-panel" method="post" action="{{ route('admin.card-batches.store', ['adminPath' => $adminPath]) }}">
                @csrf
                <header><div><span>GENERATE</span><h2>生成卡密批次</h2></div><em>1–500 张</em></header>
                <div class="panel-body stack-form compact-form">
                    <label class="field"><span>卡种</span>
                        <select name="card_type_id" required>
                            <option value="">请选择卡种</option>
                            @foreach ($cardTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->application->name }} / {{ $type->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field"><span>生成数量</span><input type="number" name="quantity" min="1" max="500" value="10" required></label>
                    <label class="field"><span>批次备注</span><textarea name="note" rows="3" maxlength="500" placeholder="例如 7 月手动发卡库存"></textarea></label>
                    <div class="info-panel"><span>i</span><p>卡号与密码使用系统随机源生成。明文只在生成完成页面展示一次，列表默认脱敏。</p></div>
                    <button class="button button-primary" type="submit">生成安全卡密</button>
                </div>
            </form>
        @endcan
    </section>

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
