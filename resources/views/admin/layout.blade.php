<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1b3473">
    <title>@yield('title', '管理后台') · {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css?v=20260725-quick-mobile-input">
    <script src="/assets/js/mybsphp.js?v=20260725-quick-time-confirm" defer></script>
    @stack('head')
</head>
<body class="dashboard-page">
<header class="admin-topbar">
    <div class="admin-topbar-main">
        <div class="admin-brand"><span class="brand-mark">BS</span><strong>{{ $appName }}</strong></div>
        <div class="topbar-desktop-tools" aria-label="后台显示控制">
            <button type="button" data-workspace-sidebar-toggle aria-label="切换侧栏显示" title="切换侧栏显示">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path><path d="m8 9-3 3 3 3"></path></svg>
            </button>
            <button type="button" data-page-refresh aria-label="刷新当前页面" title="刷新当前页面">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.5 8A8 8 0 1 0 20 15"></path><path d="M19.5 3v5h-5"></path></svg>
            </button>
        </div>
    </div>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="打开导航" aria-expanded="false">☰</button>
    <div class="topbar-actions">
        <details class="quick-entry-menu" data-quick-entry>
            <summary>快捷入口指引 <i aria-hidden="true"></i></summary>
            <div class="quick-entry-dropdown">
                <header><strong>{{ $administrator->username }}</strong><small>{{ ($databaseHealthy ?? true) ? '系统运行正常' : '数据库异常' }}</small></header>
                <a href="{{ route('agent.login') }}" target="_blank" rel="noopener">代理后端</a>
                <a href="{{ route('activation.query') }}" target="_blank" rel="noopener">激活码查询</a>
                <a href="{{ route('admin.administrators.index', ['adminPath' => $adminPath]) }}#current-security">修改密码</a>
                <form method="post" action="{{ route('admin.logout', ['adminPath' => $adminPath]) }}">
                    @csrf
                    <button type="submit">退出</button>
                </form>
            </div>
        </details>
    </div>
</header>

<div class="dashboard-layout">
    <aside class="admin-sidebar" data-sidebar>
        @include('admin.partials.navigation')
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="admin-workspace">
        @include('admin.partials.workspace-tabs')

        <main class="dashboard-content" data-workspace-content>
            @if (session('success'))
                <div class="notice notice-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="form-alert" role="alert" tabindex="-1" data-error-summary>
                    <span>!</span>
                    <div>
                        <strong>请检查以下内容</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<nav class="mobile-bottom-nav" aria-label="手机快捷导航">
    <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
       href="{{ route('admin.dashboard', ['adminPath' => $adminPath]) }}"><span>⌂</span>首页</a>
    @can('applications.view')
        <a class="{{ request()->routeIs('admin.applications.*') ? 'is-active' : '' }}"
           data-workspace-base-link
           href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}"><span>◫</span>应用</a>
    @endcan
    @can('app_accounts.manage')
        <a class="{{ request()->routeIs('admin.quick-operations.*') ? 'is-active' : '' }}"
           href="{{ route('admin.quick-operations.index', ['adminPath' => $adminPath]) }}"><span>＋</span>快办</a>
    @endcan
</nav>
</body>
</html>
