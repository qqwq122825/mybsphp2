<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '代理控制台') - {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css?v=20260812-select-scroll">
    <link rel="stylesheet" href="/assets/css/agent.css?v=20260906-query-wide">
    <script src="/assets/js/custom-select.js?v=20260812-select-scroll" defer></script>
    <script src="/assets/js/mybsphp.js?v=20260812-select-scroll" defer></script>
</head>
<body class="agent-portal-page">
@include('partials.toasts')
<div class="agent-shell" data-agent-shell>
    <aside class="agent-sidebar">
        <a class="agent-sidebar-brand" href="{{ route('agent.dashboard') }}">BSPH-代理</a>
        <nav class="agent-nav" aria-label="代理平台导航">
            <a class="{{ request()->routeIs('agent.dashboard') ? 'is-active' : '' }}" href="{{ route('agent.dashboard') }}"><i>⌂</i><span>中控台</span></a>
            <a class="{{ request()->routeIs('agent.cards.index') ? 'is-active' : '' }}" href="{{ route('agent.cards.index') }}"><i>▤</i><span>卡管理</span></a>
            <a class="{{ request()->routeIs('agent.cards.purchase') ? 'is-active' : '' }}" href="{{ route('agent.cards.purchase') }}"><i>⌑</i><span>余额制卡</span></a>
            <a class="{{ request()->routeIs('agent.finance') ? 'is-active' : '' }}" href="{{ route('agent.finance') }}"><i>➤</i><span>财务管理</span></a>
        </nav>
    </aside>

    <header class="agent-topbar">
        <div class="agent-topbar-actions">
            <button type="button" data-agent-sidebar-toggle aria-label="切换侧栏">☷</button>
            <button type="button" onclick="window.location.reload()" aria-label="刷新">↻</button>
        </div>
        <div class="agent-account-menu">
            <span>{{ $agent->username }}({{ $agent->agent_level }}星)</span>
            <form method="post" action="{{ route('agent.logout') }}">@csrf<button type="submit">退出</button></form>
        </div>
    </header>

    <main class="agent-main">
        @yield('content')
    </main>
</div>
<script>
(()=>{const shell=document.querySelector('[data-agent-shell]'),button=document.querySelector('[data-agent-sidebar-toggle]');button?.addEventListener('click',()=>shell?.classList.toggle('is-sidebar-collapsed'));})();
</script>
@stack('scripts')
</body>
</html>
