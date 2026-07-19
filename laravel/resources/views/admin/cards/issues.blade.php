@extends('admin.layout')

@section('title', '手动发卡')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">MANUAL ISSUE</p>
            <h1>手动发卡</h1>
            <p>从库存中选择卡种，手动交付给线下客户。</p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.cards.index', ['adminPath' => $adminPath]) }}">返回卡密列表</a>
    </header>

    @if ($issuedCredentials !== [])
        <section class="secret-panel credential-panel">
            <div><span>ISSUED CREDENTIALS · 本次交付</span><strong>{{ count($issuedCredentials) }} 张卡</strong></div>
            <textarea readonly rows="{{ min(10, count($issuedCredentials) + 1) }}">@foreach($issuedCredentials as $credential){{ $credential['code'] }} | {{ $credential['secret'] }}
@endforeach</textarea>
            <a class="button button-secondary" href="{{ route('demo.activate') }}" target="_blank">打开激活模拟页</a>
        </section>
    @endif

    <section class="management-grid">
        @can('cards.issue')
            <form class="dashboard-panel form-panel" method="post" action="{{ route('admin.card-issues.store', ['adminPath' => $adminPath]) }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                <header><div><span>ISSUE</span><h2>创建发卡单</h2></div><em>原子出库</em></header>
                <div class="panel-body stack-form">
                    <label class="field"><span>卡种与可用库存</span>
                        <select name="card_type_id" required>
                            <option value="">请选择卡种</option>
                            @foreach($cardTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->application->name }} / {{ $type->name }}（库存 {{ $type->inventory_count }}）</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field"><span>数量</span><input type="number" name="quantity" min="1" max="100" value="1" required></label>
                    <label class="field"><span>线下接收人</span><input name="recipient" maxlength="160" placeholder="客户名称、订单号或备注标识" required></label>
                    <label class="field"><span>发卡备注</span><textarea name="note" rows="3" maxlength="500" placeholder="本次交付说明"></textarea></label>
                    <div class="info-panel"><span>i</span><p>提交时锁定库存卡行，发卡单与卡状态在同一事务完成，库存不足时整单回滚。</p></div>
                    <button class="button button-primary" type="submit">确认手动发卡</button>
                </div>
            </form>
        @endcan

        <section class="dashboard-panel status-panel">
            <header><div><span>FLOW</span><h2>当前流程</h2></div><em class="ok-badge">手动模式</em></header>
            <dl>
                <div><dt>线上支付</dt><dd>暂未接入</dd></div>
                <div><dt>邮件/验证码</dt><dd>首版排除</dd></div>
                <div><dt>发卡方式</dt><dd>后台手动</dd></div>
                <div><dt>激活验证</dt><dd><a href="{{ route('demo.activate') }}" target="_blank">打开模拟页 ↗</a></dd></div>
            </dl>
        </section>
    </section>

    <section class="dashboard-panel data-panel" id="records">
        <header><div><span>ISSUE RECORDS</span><h2>发卡记录</h2></div><em>{{ $issues->total() }} 条</em></header>
        @if($issues->isEmpty())
            <div class="empty-activity"><b>↗</b><strong>暂无发卡记录</strong><p>创建第一笔线下发卡单。</p></div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>发卡单</th><th>应用</th><th>接收人</th><th>数量</th><th>状态</th><th>时间</th><th>备注</th></tr></thead>
                    <tbody>
                    @foreach($issues as $issue)
                        <tr>
                            <td><strong>{{ $issue->issue_no }}</strong><small>{{ $issue->public_id }}</small></td>
                            <td>{{ $issue->application->name }}</td>
                            <td>{{ $issue->recipient_display_mask ?: '线下客户' }}</td>
                            <td>{{ $issue->quantity }}</td>
                            <td><span class="status-tag status-{{ $issue->status }}">{{ $issue->status }}</span></td>
                            <td>{{ $issue->committed_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $issue->note ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
