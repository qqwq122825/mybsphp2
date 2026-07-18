<?php
declare(strict_types=1);
/** @var string $appName */
/** @var string $adminPath */
/** @var array{user_id:int,username:string} $administrator */
/** @var string $csrfToken */

$basePath = '/' . rawurlencode($adminPath);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#172549">
    <title>控制台 · <?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/admin.js" defer></script>
</head>
<body class="admin-page">
<header class="admin-topbar">
    <div class="topbar-brand">
        <button class="icon-button mobile-menu-trigger" type="button"
                data-sidebar-toggle aria-label="打开导航" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <a href="<?= $basePath ?>" class="brand-wordmark">
            <span>BS</span>
            <strong>BSPH</strong>
        </a>
    </div>
    <div class="topbar-tools">
        <button class="icon-button desktop-menu-trigger" type="button"
                data-sidebar-collapse aria-label="折叠侧栏">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16M4 12h10M4 18h16"/>
            </svg>
        </button>
        <button class="icon-button" type="button" data-page-refresh aria-label="刷新页面">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 6v5h-5M4 18v-5h5M6.1 9A7 7 0 0 1 18 6l2 5M17.9 15A7 7 0 0 1 6 18l-2-5"/>
            </svg>
        </button>
    </div>
    <div class="topbar-account">
        <span class="system-online"><i></i>系统正常</span>
        <div class="account-chip">
            <span class="account-avatar"><?= htmlspecialchars(strtoupper(substr($administrator['username'], 0, 1))) ?></span>
            <span>
                <small>超级管理员</small>
                <strong><?= htmlspecialchars($administrator['username']) ?></strong>
            </span>
        </div>
        <form method="post" action="<?= $basePath ?>/logout">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button class="topbar-logout" type="submit">退出</button>
        </form>
    </div>
</header>

<aside class="admin-sidebar" data-sidebar>
    <nav class="sidebar-nav" aria-label="后台主导航">
        <section class="nav-group">
            <h2>控制台</h2>
            <div class="nav-group__items">
                <a class="nav-item active" href="<?= $basePath ?>">
                    <span class="nav-icon">⌂</span>
                    <span>首页</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">◉</span>
                    <span>在线用户</span>
                    <small>0</small>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">▤</span>
                    <span>系统日志</span>
                </a>
            </div>
        </section>

        <section class="nav-group">
            <h2>应用管理</h2>
            <div class="nav-group__items">
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">◇</span>
                    <span>软件管理</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">＋</span>
                    <span>添加应用</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">◎</span>
                    <span>风险策略</span>
                </a>
            </div>
        </section>

        <section class="nav-group">
            <h2>卡密与发卡</h2>
            <div class="nav-group__items">
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">▱</span>
                    <span>卡密管理</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">▦</span>
                    <span>批次管理</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">↗</span>
                    <span>手动发卡</span>
                </a>
            </div>
        </section>

        <section class="nav-group">
            <h2>账号管理</h2>
            <div class="nav-group__items">
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">♙</span>
                    <span>用户管理</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">♢</span>
                    <span>代理管理</span>
                </a>
                <a class="nav-item" href="#" aria-disabled="true">
                    <span class="nav-icon">⚙</span>
                    <span>系统设置</span>
                </a>
            </div>
        </section>
    </nav>
    <div class="sidebar-version">
        <span>MyBsPHP2</span>
        <small>Open Source Preview</small>
    </div>
</aside>

<button class="sidebar-overlay" type="button" data-sidebar-overlay aria-label="关闭导航"></button>

<main class="admin-main">
    <div class="workspace-tabs">
        <div class="breadcrumb">
            <span>⌂</span>
            <span>控制台</span>
            <strong>首页</strong>
        </div>
        <div class="workspace-tab active">
            <span>首页</span>
            <i></i>
        </div>
    </div>

    <div class="admin-content">
        <section class="page-heading">
            <div>
                <p class="page-eyebrow">DASHBOARD</p>
                <h1>控制台</h1>
                <p>欢迎回来，<?= htmlspecialchars($administrator['username']) ?>。这是系统今日概览。</p>
            </div>
            <div class="heading-actions">
                <button class="secondary-button" type="button" disabled>查看日志</button>
                <button class="primary-button" type="button" disabled>
                    <span aria-hidden="true">＋</span> 创建应用
                </button>
            </div>
        </section>

        <section class="stat-grid" aria-label="系统指标">
            <article class="stat-card">
                <span class="stat-icon stat-icon--blue">⌁</span>
                <div>
                    <p>今日验证</p>
                    <strong>0</strong>
                    <small><b>实时</b> 客户端请求</small>
                </div>
            </article>
            <article class="stat-card">
                <span class="stat-icon stat-icon--green">▱</span>
                <div>
                    <p>可用卡密</p>
                    <strong>0</strong>
                    <small><b>0</b> 当前库存总量</small>
                </div>
            </article>
            <article class="stat-card">
                <span class="stat-icon stat-icon--purple">◉</span>
                <div>
                    <p>在线设备</p>
                    <strong>0</strong>
                    <small><b>0</b> 活跃会话</small>
                </div>
            </article>
            <article class="stat-card">
                <span class="stat-icon stat-icon--orange">!</span>
                <div>
                    <p>风险事件</p>
                    <strong>0</strong>
                    <small><b>正常</b> 最近 24 小时</small>
                </div>
            </article>
        </section>

        <section class="dashboard-layout">
            <article class="content-panel overview-panel">
                <header class="panel-header">
                    <div>
                        <h2>应用概览</h2>
                        <p>应用、授权与库存运行情况</p>
                    </div>
                    <button class="text-button" type="button" disabled>查看全部 →</button>
                </header>
                <div class="empty-state">
                    <span class="empty-state__icon">◇</span>
                    <h3>还没有创建应用</h3>
                    <p>创建第一个应用后，可配置卡密登录、账户充值和 Legacy 接口。</p>
                    <button class="primary-button primary-button--small" type="button" disabled>创建应用</button>
                </div>
            </article>

            <article class="content-panel quick-panel">
                <header class="panel-header">
                    <div>
                        <h2>快捷操作</h2>
                        <p>常用管理入口</p>
                    </div>
                </header>
                <div class="quick-actions">
                    <button type="button" disabled>
                        <span class="quick-icon quick-icon--blue">＋</span>
                        <span><strong>创建应用</strong><small>配置授权模式</small></span>
                        <i>›</i>
                    </button>
                    <button type="button" disabled>
                        <span class="quick-icon quick-icon--green">▦</span>
                        <span><strong>生成卡密</strong><small>建立卡种和批次</small></span>
                        <i>›</i>
                    </button>
                    <button type="button" disabled>
                        <span class="quick-icon quick-icon--orange">↗</span>
                        <span><strong>手动发卡</strong><small>发给用户或代理</small></span>
                        <i>›</i>
                    </button>
                </div>
            </article>
        </section>

        <section class="content-panel activity-panel">
            <header class="panel-header">
                <div>
                    <h2>最近动态</h2>
                    <p>管理员操作、客户端验证与风险事件</p>
                </div>
                <div class="panel-filter">
                    <button class="active" type="button">全部</button>
                    <button type="button">操作</button>
                    <button type="button">风险</button>
                </div>
            </header>
            <div class="activity-empty">
                <span>▤</span>
                <p>暂无动态记录，后续操作会显示在这里。</p>
            </div>
        </section>
    </div>
</main>

<nav class="mobile-bottom-nav" aria-label="手机快捷导航">
    <a class="active" href="<?= $basePath ?>"><span>⌂</span>首页</a>
    <a href="#" aria-disabled="true"><span>◇</span>应用</a>
    <a href="#" aria-disabled="true"><span>▱</span>卡密</a>
    <a href="#" aria-disabled="true"><span>♙</span>我的</a>
</nav>
</body>
</html>
