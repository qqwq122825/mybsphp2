@extends('admin.layout')

@section('title', $application->name.' · 用户管理')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">SOFTWARE USERS</p>
            <h1>用户管理 · {{ $application->name }}</h1>
            <p>
                @if($application->mode === 'card_login')
                    卡密模式以已发出/已激活卡串作为软件用户。
                @else
                    账户模式按用户名管理到期时间、余额、点数和绑定设备。
                @endif
            </p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回软件管理</a>
    </header>

    <section class="metric-grid">
        <article class="metric-card metric-blue"><span>用户总数</span><strong>{{ $userMetrics['total'] }}</strong><small>{{ $application->mode === 'card_login' ? '已发出卡串' : '账户数' }}</small></article>
        <article class="metric-card metric-green"><span>正常使用</span><strong>{{ $userMetrics['active'] }}</strong><small>当前有效</small></article>
        <article class="metric-card metric-orange"><span>已冻结</span><strong>{{ $userMetrics['frozen'] }}</strong><small>已暂停使用</small></article>
        <article class="metric-card metric-red"><span>已过期</span><strong>{{ $userMetrics['expired'] }}</strong><small>等待续期</small></article>
    </section>

    <section class="dashboard-panel data-panel">
        <header>
            <div>
                <span>{{ $application->mode === 'card_login' ? 'CARD USERS' : 'ACCOUNT USERS' }}</span>
                <h2>{{ $application->mode === 'card_login' ? '卡串用户' : '账户用户' }}</h2>
            </div>
            <em>{{ $users->total() }} 条</em>
        </header>
        <form class="filter-bar" method="get">
            <input name="keyword" value="{{ request('keyword') }}"
                   placeholder="{{ $application->mode === 'card_login' ? '卡号后四位 / ULID' : '用户名' }}">
            <select name="status">
                <option value="">全部状态</option>
                @foreach(['issued' => '已发出', 'active' => '正常', 'frozen' => '冻结', 'expired' => '过期', 'revoked' => '作废'] as $value => $label)
                    @if($application->mode === 'card_login' || in_array($value, ['active', 'frozen', 'expired'], true))
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endif
                @endforeach
            </select>
            <button class="button button-secondary" type="submit">搜索</button>
        </form>

        @if($users->isEmpty())
            <div class="empty-activity">
                <b>◇</b>
                <strong>暂无{{ $application->mode === 'card_login' ? '卡串用户' : '账户用户' }}</strong>
                <p>{{ $application->mode === 'card_login' ? '现卡制作并发出后会显示在这里。' : '后续 registration.lg 注册的账户会显示在这里。' }}</p>
            </div>
        @else
            <div class="table-scroll">
                @if($application->mode === 'card_login')
                    <table class="data-table">
                        <thead><tr><th>卡串</th><th>卡串类型</th><th>状态</th><th>发出时间</th><th>激活时间</th><th>到期时间</th><th>绑定设备</th></tr></thead>
                        <tbody>
                        @foreach($users as $card)
                            <tr>
                                <td><strong>••••-{{ $card->code_last4 }}</strong><small>{{ $card->public_id }}</small></td>
                                <td>{{ $card->cardType->name }}</td>
                                <td><span class="status-tag status-{{ $card->status }}">{{ $card->status }}</span></td>
                                <td>{{ $card->issued_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $card->activated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $card->expires_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $card->bound_device_mask ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="data-table">
                        <thead><tr><th>用户名</th><th>状态</th><th>到期时间</th><th>余额</th><th>点数</th><th>最后登录</th><th>绑定设备</th></tr></thead>
                        <tbody>
                        @foreach($users as $account)
                            <tr>
                                <td><strong>{{ $account->username }}</strong><small>{{ $account->public_id }}</small></td>
                                <td><span class="status-tag status-{{ $account->status }}">{{ $account->status }}</span></td>
                                <td>{{ $account->expires_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>¥{{ number_format($account->balance_cents / 100, 2) }}</td>
                                <td>{{ $account->points }}</td>
                                <td>{{ $account->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $account->bound_device_mask ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="pager">
                @if($users->previousPageUrl())<a href="{{ $users->previousPageUrl() }}">上一页</a>@endif
                <span>第 {{ $users->currentPage() }} / {{ $users->lastPage() }} 页</span>
                @if($users->nextPageUrl())<a href="{{ $users->nextPageUrl() }}">下一页</a>@endif
            </div>
        @endif
    </section>
@endsection
