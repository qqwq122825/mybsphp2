@extends('admin.layout')

@section('title', '管理首页')

@section('content')
    <header class="page-heading">
        <div>
            <p class="eyebrow">DASHBOARD</p>
            <h1>管理首页</h1>
            <p>{{ $administrator->username }}，欢迎回来。以下数据来自当前数据库。</p>
        </div>
        <time datetime="{{ now()->toAtomString() }}">{{ now()->format('Y-m-d H:i') }}</time>
    </header>

    <section class="metric-grid" aria-label="系统指标">
        <article class="metric-card metric-blue">
            <span>管理员</span><strong>{{ $metrics['administrators'] }}</strong><small>当前启用账号</small>
        </article>
        <article class="metric-card metric-green">
            <span>今日登录</span><strong>{{ $metrics['logins_today'] }}</strong><small>成功登录次数</small>
        </article>
        <article class="metric-card metric-orange">
            <span>今日操作</span><strong>{{ $metrics['operations_today'] }}</strong><small>审计事件总数</small>
        </article>
        <article class="metric-card metric-red">
            <span>失败登录</span><strong>{{ $metrics['failed_logins_today'] }}</strong><small>今日风险信号</small>
        </article>
    </section>

    <section class="dashboard-grid">
        @canany(['applications.create', 'cards.issue', 'agents.view', 'risk.view'])
            <article class="dashboard-panel quick-panel">
                <header><div><span>快捷操作</span><h2>开始管理业务</h2></div><em>常用入口</em></header>
                <div class="quick-grid">
                    @can('applications.create')
                        <a href="{{ route('admin.applications.create', ['adminPath' => $adminPath]) }}">
                            <b>＋</b><span><strong>创建应用</strong><small>配置授权模式与协议</small></span>
                        </a>
                    @endcan
                    @can('cards.view')
                        <a href="{{ route('admin.cards.index', ['adminPath' => $adminPath]) }}">
                            <b>◇</b><span><strong>生成卡密</strong><small>创建卡种与批次</small></span>
                        </a>
                    @endcan
                    @can('cards.issue')
                        <a href="{{ route('admin.card-issues.index', ['adminPath' => $adminPath]) }}">
                            <b>↗</b><span><strong>手动发卡</strong><small>线下交付与发卡记录</small></span>
                        </a>
                    @endcan
                    <a href="{{ route('demo.activate') }}" target="_blank">
                        <b>⌁</b><span><strong>激活模拟</strong><small>测试卡密与设备绑定</small></span>
                    </a>
                </div>
            </article>
        @endcanany

        <article class="dashboard-panel status-panel">
            <header><div><span>运行状态</span><h2>系统环境</h2></div><em class="ok-badge">正常</em></header>
            <dl>
                <div><dt>Laravel</dt><dd>{{ app()->version() }}</dd></div>
                <div><dt>PHP</dt><dd>{{ PHP_VERSION }}</dd></div>
                <div><dt>数据库</dt><dd>{{ $databaseHealthy ? '连接正常' : '连接异常' }}</dd></div>
                <div><dt>时区</dt><dd>{{ config('app.timezone') }}</dd></div>
            </dl>
        </article>
    </section>

    @if ($canViewAudit)
        <section class="dashboard-panel activity-panel" id="audit">
            <header>
                <div><span>最近动态</span><h2>管理与登录审计</h2></div>
                <em>{{ count($recentActivity) }} 条</em>
            </header>
            @if ($recentActivity === [])
                <div class="empty-activity"><b>◎</b><strong>暂无审计记录</strong><p>登录和管理操作将在这里显示。</p></div>
            @else
                <div class="activity-list">
                    @foreach ($recentActivity as $activity)
                        <article>
                            <span class="activity-dot {{ $activity->result === 'success' ? 'is-success' : 'is-danger' }}"></span>
                            <div>
                                <strong>{{ $activity->action }}</strong>
                                <small>{{ $activity->target_type }}{{ $activity->target_id ? ' #'.$activity->target_id : '' }}</small>
                            </div>
                            <time>{{ $activity->created_at?->format('m-d H:i:s') }}</time>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endsection
