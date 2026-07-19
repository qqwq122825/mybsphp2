<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1b3473">
    <title>@yield('title', '管理后台') · {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css">
    <script src="/assets/js/mybsphp.js" defer></script>
    @stack('head')
</head>
<body class="dashboard-page">
<header class="admin-topbar">
    <div class="admin-brand"><span class="brand-mark">BS</span><strong>{{ $appName }}</strong></div>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="打开导航" aria-expanded="false">☰</button>
    <div class="topbar-actions">
        <span class="system-health {{ ($databaseHealthy ?? true) ? 'is-online' : 'is-offline' }}">
            <i></i>{{ ($databaseHealthy ?? true) ? '系统运行正常' : '数据库异常' }}
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
        @include('admin.partials.navigation')
        <div class="sidebar-foot">
            <span>后台入口</span><code>/{{ $adminPath }}</code>
        </div>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <main class="dashboard-content">
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

<nav class="mobile-bottom-nav" aria-label="手机快捷导航">
    <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
       href="{{ route('admin.dashboard', ['adminPath' => $adminPath]) }}"><span>⌂</span>首页</a>
    @can('applications.view')
        <a class="{{ request()->routeIs('admin.applications.*') ? 'is-active' : '' }}"
           href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}"><span>◫</span>应用</a>
    @endcan
    @can('cards.view')
        <a class="{{ request()->routeIs('admin.cards.*') ? 'is-active' : '' }}"
           href="{{ route('admin.cards.index', ['adminPath' => $adminPath]) }}"><span>◇</span>卡密</a>
    @endcan
    <a href="{{ route('demo.activate') }}"><span>⌁</span>激活</a>
</nav>
</body>
</html>
