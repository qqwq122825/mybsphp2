<nav class="admin-workspace-bar" aria-label="页面标签导航">
    <div class="workspace-controls">
        <button type="button" data-workspace-sidebar-toggle aria-label="收起或展开侧栏" title="收起或展开侧栏">
            <span aria-hidden="true">≪</span>
        </button>
        <a href="{{ route('admin.dashboard', ['adminPath' => $adminPath]) }}"
           aria-label="后台首页" title="后台首页">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9.5 20v-6h5v6"/></svg>
        </a>
    </div>

    <div class="workspace-tabs" role="list" data-workspace-tabs>
        @if (request()->routeIs('admin.software-card-types.*', 'admin.software-card-making.*'))
            <a class="workspace-tab" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">
                <span>软件管理</span><i aria-hidden="true">×</i>
            </a>
            @if(isset($application) && $application)
                <span class="workspace-tab is-active" aria-current="page">
                    <span>{{ $application->code }}-{{ request()->routeIs('admin.software-card-types.*') ? '卡串类型' : '现卡制作' }}</span><i aria-hidden="true">×</i>
                </span>
            @endif
        @elseif (request()->routeIs('admin.applications.*'))
            <a class="workspace-tab {{ request()->routeIs('admin.applications.index') ? 'is-active' : '' }}"
               href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">
                <span>软件管理</span><i aria-hidden="true">×</i>
            </a>
            @if(request()->routeIs('admin.applications.create'))
                <a class="workspace-tab {{ request()->routeIs('admin.applications.create') ? 'is-active' : '' }}"
                   href="{{ route('admin.applications.create', ['adminPath' => $adminPath]) }}">
                    <span>添加新软件</span><i aria-hidden="true">×</i>
                </a>
            @endif
            @if (request()->routeIs('admin.applications.edit') && isset($application) && $application)
                <span class="workspace-tab is-active" aria-current="page">
                    <span>{{ $application->code }}-软件配置</span><i aria-hidden="true">×</i>
                </span>
            @elseif (request()->routeIs('admin.applications.users.index') && isset($application) && $application)
                <span class="workspace-tab is-active" aria-current="page">
                    <span>{{ $application->code }}-用户管理</span><i aria-hidden="true">×</i>
                </span>
            @endif
        @elseif (request()->routeIs('admin.cards.*'))
            <span class="workspace-tab is-active" aria-current="page"><span>卡密管理</span><i aria-hidden="true">×</i></span>
        @elseif (request()->routeIs('admin.card-issues.*'))
            <span class="workspace-tab is-active" aria-current="page"><span>手动发卡</span><i aria-hidden="true">×</i></span>
        @elseif (request()->routeIs('admin.administrators.*'))
            <span class="workspace-tab is-active" aria-current="page"><span>后台管理员</span><i aria-hidden="true">×</i></span>
        @else
            <span class="workspace-tab is-active" aria-current="page"><span>控制台首页</span><i aria-hidden="true">×</i></span>
        @endif
    </div>

    <button class="workspace-refresh" type="button" data-page-refresh aria-label="刷新当前页面" title="刷新当前页面">↻</button>
</nav>
