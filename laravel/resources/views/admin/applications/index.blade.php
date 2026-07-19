@extends('admin.layout')

@section('title', '软件管理')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">SOFTWARE MANAGEMENT</p>
            <h1>软件管理</h1>
            <p>集中查看软件模式、卡密数据和运行状态。</p>
        </div>
        @can('applications.create')
            <a class="button button-primary" href="{{ route('admin.applications.create', ['adminPath' => $adminPath]) }}">＋ 添加新软件</a>
        @endcan
    </header>

    <section class="metric-grid">
        <article class="metric-card metric-blue"><span>软件总数</span><strong>{{ $metrics['total'] }}</strong><small>当前后台全部软件</small></article>
        <article class="metric-card metric-green"><span>正常开放</span><strong>{{ $metrics['active'] }}</strong><small>客户端可正常访问</small></article>
        <article class="metric-card metric-orange"><span>卡密验证</span><strong>{{ $metrics['card_login'] }}</strong><small>卡密直接登录</small></article>
        <article class="metric-card metric-red"><span>账户模式</span><strong>{{ $metrics['account_recharge'] }}</strong><small>注册账户并充值</small></article>
    </section>

    @if ($applications->isEmpty())
        <section class="dashboard-panel data-panel">
            <div class="empty-activity">
                <b>◫</b><strong>还没有软件</strong><p>先添加软件，再建立卡种并生成卡密。</p>
            </div>
        </section>
    @else
        <section class="software-card-list" aria-label="软件列表">
            @foreach ($applications as $application)
                @php($notActivated = max(0, $application->cards_count - $application->activated_cards_count))
                <article class="software-card">
                    <header>
                        <div class="software-card-title">
                            <b>◫</b>
                            <div>
                                <h2>{{ $application->name }}</h2>
                                <p>代号：<strong>{{ $application->code }}</strong></p>
                            </div>
                        </div>
                        <span class="status-tag status-{{ $application->status }}">
                            {{ $application->status === 'active' ? '正常开放' : ($application->status === 'suspended' ? '暂停使用' : $application->status) }}
                        </span>
                    </header>

                    <div class="software-mode-line">
                        <span>软件模式</span>
                        <strong>{{ $application->mode === 'card_login' ? '卡密验证限时模式' : '账户注册与充值模式' }}</strong>
                    </div>

                    <div class="software-stats">
                        <div><span>卡总数</span><strong>{{ $application->cards_count }}</strong></div>
                        <div><span>卡已激活</span><strong>{{ $application->activated_cards_count }}</strong></div>
                        <div><span>卡未激活</span><strong>{{ $notActivated }}</strong></div>
                        <div><span>卡类型数量</span><strong>{{ $application->card_types_count }}</strong></div>
                        <div><span>版本号</span><strong>{{ $application->version_name ?: '—' }}</strong></div>
                    </div>

                    <footer class="software-card-actions">
                        @can('applications.update')
                            <a class="software-action-primary" href="{{ route('admin.applications.edit', ['adminPath' => $adminPath, 'application' => $application]) }}">⚙ 配置软件</a>
                        @endcan
                        @can('cards.view')
                            <a href="{{ route('admin.cards.index', ['adminPath' => $adminPath, 'application' => $application->getKey()]) }}">▣ 充值卡列表</a>
                        @endcan
                        @can('applications.status.update')
                            <form method="post" action="{{ route('admin.applications.toggle', ['adminPath' => $adminPath, 'application' => $application]) }}">
                                @csrf
                                <button type="submit">{{ $application->status === 'active' ? '暂停软件' : '启用软件' }}</button>
                            </form>
                        @endcan
                    </footer>
                </article>
            @endforeach
        </section>

        <div class="pager software-pager">
            @if ($applications->previousPageUrl())<a href="{{ $applications->previousPageUrl() }}">上一页</a>@endif
            <span>第 {{ $applications->currentPage() }} / {{ $applications->lastPage() }} 页</span>
            @if ($applications->nextPageUrl())<a href="{{ $applications->nextPageUrl() }}">下一页</a>@endif
        </div>
    @endif
@endsection
