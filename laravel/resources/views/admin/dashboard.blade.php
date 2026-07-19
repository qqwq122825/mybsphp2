<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1b3473">
    <title>管理首页 · {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css">
    <script src="/assets/js/mybsphp.js" defer></script>
</head>
<body class="dashboard-page">
<header class="admin-topbar">
    <div class="admin-brand"><span class="brand-mark">BS</span><strong>{{ $appName }}</strong></div>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="打开导航" aria-expanded="false">☰</button>
    <div class="topbar-actions">
        <span class="system-health {{ $databaseHealthy ? 'is-online' : 'is-offline' }}">
            <i></i>{{ $databaseHealthy ? '系统运行正常' : '数据库异常' }}
        </span>
        <span class="admin-identity"><small>当前管理员</small><strong>{{ $administrator->username }}</strong></span>
        <form method="post" action="{{ route('admin.logout', ['adminPath' => $adminPath]) }}">
            @csrf
            <button class="topbar-logout" type="submit">退出</button>
        </form>
    </div>
</header>

<div class="dashboard-layout">
    <aside class="admin-sidebar" data-sidebar>
        <nav aria-label="管理后台导航">
            <p>工作台</p>
            <a class="is-active" href="{{ route('admin.dashboard', ['adminPath' => $adminPath]) }}"><span>⌂</span>概览</a>
            <p>业务管理</p>
            <a href="#applications"><span>◫</span>应用</a>
            <a href="#cards"><span>◇</span>卡密</a>
            <a href="#accounts"><span>♙</span>账户</a>
            <a href="#agents"><span>♧</span>代理</a>
            <p>安全与系统</p>
            <a href="#risk"><span>△</span>在线与风险</a>
            <a href="#audit"><span>≡</span>日志与审计</a>
            <a href="#settings"><span>⚙</span>系统设置</a>
        </nav>
        <div class="sidebar-foot">
            <span>后台入口</span><code>/{{ $adminPath }}</code>
        </div>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <main class="dashboard-content">
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
            <article class="dashboard-panel quick-panel">
                <header><div><span>快捷操作</span><h2>开始管理业务</h2></div><em>常用入口</em></header>
                <div class="quick-grid">
                    <a href="#applications"><b>＋</b><span><strong>创建应用</strong><small>配置授权模式与协议</small></span></a>
                    <a href="#cards"><b>◇</b><span><strong>生成卡密</strong><small>创建卡种与批次</small></span></a>
                    <a href="#agents"><b>♧</b><span><strong>管理代理</strong><small>等级、余额与权限</small></span></a>
                    <a href="#risk"><b>△</b><span><strong>查看风险</strong><small>登录和设备事件</small></span></a>
                </div>
            </article>

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
    </main>
</div>

<nav class="mobile-bottom-nav" aria-label="手机快捷导航">
    <a class="is-active" href="#"><span>⌂</span>首页</a>
    <a href="#applications"><span>◫</span>应用</a>
    <a href="#cards"><span>◇</span>卡密</a>
    <a href="#settings"><span>⚙</span>我的</a>
</nav>
</body>
</html>
