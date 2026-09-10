@php
    $isApplicationWorkspace = request()->routeIs('admin.applications.index', 'admin.applications.edit', 'admin.risk-control.index');
    $workspaceBaseUrl = $isApplicationWorkspace
        ? route('admin.applications.index', ['adminPath' => $adminPath])
        : null;
    $workspaceBootstrapUrl = request()->routeIs('admin.applications.edit', 'admin.risk-control.index')
        ? request()->fullUrl()
        : null;
    // Display names are presentation only; workspace identity stays in the route URL.
    $softwareTabTitle = static fn ($software, $page) => $software ? $software->name.' · '.$page : $page;
    $workspacePageTitle = match (true) {
        request()->routeIs('admin.applications.edit') => $softwareTabTitle($application ?? null, '软件配置'),
        request()->routeIs('admin.applications.users.*') => $softwareTabTitle($application ?? null, '用户管理'),
        request()->routeIs('admin.risk-control.index') => $softwareTabTitle($selectedApplication ?? null, '封控设置'),
        request()->routeIs('admin.software-card-types.*') => $softwareTabTitle($application ?? null, '卡串类型'),
        request()->routeIs('admin.software-card-making.*') => $softwareTabTitle($application ?? null, '现卡制作'),
        request()->routeIs('admin.cards.maintenance.*') => '充值卡批量维护',
        request()->routeIs('admin.cards.*') => $softwareTabTitle($applicationFilter ?? null, '充值卡列表'),
        default => null,
    };
    $workspaceBootstrapTitle = $workspaceBootstrapUrl ? $workspacePageTitle : null;
@endphp

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

    <div class="workspace-tabs" role="list" data-workspace-tabs
         @if($workspacePageTitle) data-workspace-page-title="{{ $workspacePageTitle }}" @endif
         @if($workspaceBaseUrl) data-workspace-base-url="{{ $workspaceBaseUrl }}" @endif
         @if($workspaceBootstrapUrl)
             data-workspace-bootstrap-url="{{ $workspaceBootstrapUrl }}"
             data-workspace-bootstrap-title="{{ $workspaceBootstrapTitle }}"
         @endif>
        @if (request()->routeIs('admin.software-card-types.*', 'admin.software-card-making.*'))
            <a class="workspace-tab" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">
                <span>软件管理</span><i aria-hidden="true">×</i>
            </a>
            @if(isset($application) && $application)
                <span class="workspace-tab is-active" aria-current="page">
                    <span title="{{ $workspacePageTitle }}">{{ $workspacePageTitle }}</span>
                    <a class="workspace-static-close" data-workspace-static-close
                       href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}"
                       aria-label="关闭当前标签" title="关闭当前标签">×</a>
                </span>
            @endif
        @elseif (request()->routeIs('admin.applications.*'))
            <a class="workspace-tab {{ request()->routeIs('admin.applications.index') ? 'is-active' : '' }}"
               data-workspace-base-tab
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
                    <span title="{{ $workspacePageTitle }}">{{ $workspacePageTitle }}</span><i aria-hidden="true">×</i>
                </span>
            @elseif (request()->routeIs('admin.applications.users.index') && isset($application) && $application)
                <span class="workspace-tab is-active" aria-current="page">
                    <span title="{{ $workspacePageTitle }}">{{ $workspacePageTitle }}</span><i aria-hidden="true">×</i>
                </span>
            @endif
        @elseif (request()->routeIs('admin.risk-control.center'))
            <span class="workspace-tab is-active" aria-current="page"><span>封控中心</span><i aria-hidden="true">×</i></span>
        @elseif (request()->routeIs('admin.risk-control.index'))
            <a class="workspace-tab" data-workspace-base-tab href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">
                <span>软件管理</span><i aria-hidden="true">×</i>
            </a>
            <span class="workspace-tab is-active" aria-current="page">
                <span title="{{ $workspacePageTitle }}">{{ $workspacePageTitle }}</span><i aria-hidden="true">×</i>
            </span>
        @elseif (request()->routeIs('admin.cards.*'))
            <a class="workspace-tab" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">
                <span>软件管理</span><i aria-hidden="true">×</i>
            </a>
            <span class="workspace-tab is-active" aria-current="page">
                <span title="{{ $workspacePageTitle }}">
                    {{ $workspacePageTitle }}
                </span><i aria-hidden="true">×</i>
            </span>
        @elseif (request()->routeIs('admin.card-issues.*'))
            <span class="workspace-tab is-active" aria-current="page"><span>手动发卡</span><i aria-hidden="true">×</i></span>
        @elseif (request()->routeIs('admin.system-logs.*'))
            <span class="workspace-tab is-active" aria-current="page"><span>{{ $title ?? '系统日志' }}</span><i aria-hidden="true">×</i></span>
        @elseif (request()->routeIs('admin.administrators.*'))
            <a class="workspace-tab {{ request()->routeIs('admin.administrators.index') ? 'is-active' : '' }}"
               href="{{ route('admin.administrators.index', ['adminPath' => $adminPath]) }}">
                <span>管理员列表</span><i aria-hidden="true">×</i>
            </a>
            @if(request()->routeIs('admin.administrators.create'))
                <span class="workspace-tab is-active" aria-current="page"><span>添加管理员</span><i aria-hidden="true">×</i></span>
            @endif
        @elseif (request()->routeIs('admin.agents.*'))
            <a class="workspace-tab {{ request()->routeIs('admin.agents.index') ? 'is-active' : '' }}"
               href="{{ route('admin.agents.index', ['adminPath' => $adminPath]) }}">
                <span>代理账号管理</span><i aria-hidden="true">×</i>
            </a>
            @if(request()->routeIs('admin.agents.create'))
                <span class="workspace-tab is-active" aria-current="page"><span>添加代理账号</span><i aria-hidden="true">×</i></span>
            @endif
        @elseif (request()->routeIs('admin.operation-plugins.*', 'admin.quick-operations.*'))
            <span class="workspace-tab is-active" aria-current="page">
                <span>{{ request()->routeIs('admin.quick-operations.*') ? '快速用户操作' : ($title ?? '运营插件') }}</span><i aria-hidden="true">×</i>
            </span>
        @elseif (request()->routeIs('admin.settings.system.*'))
            <span class="workspace-tab is-active" aria-current="page"><span>系统信息设置</span><i aria-hidden="true">×</i></span>
        @else
            <span class="workspace-tab is-active" aria-current="page"><span>控制台首页</span><i aria-hidden="true">×</i></span>
        @endif
    </div>

    <button class="workspace-refresh" type="button" data-page-refresh aria-label="刷新当前页面" title="刷新当前页面">↻</button>
</nav>
