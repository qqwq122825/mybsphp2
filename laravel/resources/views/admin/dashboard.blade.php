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
            <details class="nav-group" open>
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 10.5 12 3l9 7.5"></path>
                        <path d="M5.5 9.5V21h13V9.5"></path>
                    </svg>
                    <strong>控制台</strong><i aria-hidden="true"></i>
                </summary>
                <div class="nav-children">
                    <a class="is-active" href="{{ route('admin.dashboard', ['adminPath' => $adminPath]) }}">主页</a>
                    <a href="#online">在线用户管理</a>
                    <a href="#risk">风险事件</a>
                    <a href="#audit">系统日志</a>
                    <a href="#administrators">后台管理员</a>
                </div>
            </details>
            <details class="nav-group">
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                        <path d="M14 6.5h7M17.5 3v7M3 17.5h7M6.5 14v7"></path>
                    </svg>
                    <strong>应用管理</strong><i aria-hidden="true"></i>
                </summary>
                <div class="nav-children">
                    <a href="#applications">软件管理</a>
                    <a href="#applications">添加新软件</a>
                    <a href="#applications">授权协议配置</a>
                    <a href="#accounts">软件用户批量维护</a>
                </div>
            </details>
            <details class="nav-group">
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 6.5h16v11H4z"></path>
                        <path d="M8 6.5v11M16 6.5v11M4 10h16"></path>
                    </svg>
                    <strong>卡密管理</strong><i aria-hidden="true"></i>
                </summary>
                <div class="nav-children">
                    <a href="#cards">卡密列表</a>
                    <a href="#cards">卡种与批次</a>
                    <a href="#cards">卡密批量维护</a>
                    <a href="#cards">手动发卡</a>
                    <a href="#cards">发卡记录</a>
                </div>
            </details>
            <details class="nav-group">
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="8" r="4"></circle>
                        <path d="M2.5 21c.4-4.3 2.7-6.5 6.5-6.5s6.1 2.2 6.5 6.5"></path>
                        <path d="M15.5 5.2a3.7 3.7 0 0 1 0 6.8M17 14.5c2.8.6 4.3 2.8 4.5 6.5"></path>
                    </svg>
                    <strong>账号管理</strong><i aria-hidden="true"></i>
                </summary>
                <div class="nav-children">
                    <a href="#accounts">用户账号管理</a>
                    <a href="#agents">代理账号管理</a>
                    <a href="#accounts">添加账号</a>
                    <a href="#accounts">批量维护</a>
                </div>
            </details>
            <details class="nav-group">
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"></path>
                        <path d="m4 7.5 8 4.5 8-4.5M12 12v9"></path>
                    </svg>
                    <strong>系统配置</strong><i aria-hidden="true"></i>
                </summary>
                <div class="nav-children">
                    <a href="#settings">系统信息设置</a>
                    <a href="#settings">个性化设置</a>
                    <a href="#permissions">角色与权限</a>
                    <a href="#devices">设备解绑</a>
                    <a href="#api">API 与 Legacy 配置</a>
                </div>
            </details>
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
