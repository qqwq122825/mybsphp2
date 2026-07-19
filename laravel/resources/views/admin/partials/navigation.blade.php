<nav aria-label="管理后台导航">
    @canany(['dashboard.view', 'online_users.view', 'risk.view', 'audit.view', 'administrators.view'])
        <details class="nav-group" {{ request()->routeIs('admin.dashboard') ? 'open' : '' }}>
            <summary title="控制台">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5"></path><path d="M5.5 9.5V21h13V9.5"></path>
                </svg>
                <strong>控制台</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('dashboard.view')
                    <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                       href="{{ route('admin.dashboard', ['adminPath' => $adminPath]) }}">主页</a>
                @endcan
                @can('online_users.view')<a href="#online">在线用户管理</a>@endcan
                @can('risk.view')<a href="#risk">风险事件</a>@endcan
                @can('audit.view')<a href="#audit">系统日志</a>@endcan
                @can('administrators.view')
                    <a class="{{ request()->routeIs('admin.administrators.*') ? 'is-active' : '' }}"
                       href="{{ route('admin.administrators.index', ['adminPath' => $adminPath]) }}">后台管理员</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['applications.view', 'applications.create', 'applications.protocol.manage', 'app_accounts.manage'])
        <details class="nav-group" {{ request()->routeIs('admin.applications.*') ? 'open' : '' }}>
            <summary title="应用管理">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <path d="M14 6.5h7M17.5 3v7M3 17.5h7M6.5 14v7"></path>
                </svg>
                <strong>应用管理</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('applications.view')
                    <a class="{{ request()->routeIs('admin.applications.index') ? 'is-active' : '' }}"
                       href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">软件管理</a>
                @endcan
                @can('applications.create')
                    <a class="{{ request()->routeIs('admin.applications.create') ? 'is-active' : '' }}"
                       href="{{ route('admin.applications.create', ['adminPath' => $adminPath]) }}">添加新软件</a>
                @endcan
                @can('applications.protocol.manage')<a href="#applications">授权协议配置</a>@endcan
                @can('app_accounts.manage')<a href="#accounts">软件用户批量维护</a>@endcan
            </div>
        </details>
    @endcanany

    @canany(['cards.view', 'cards.import', 'cards.issue', 'card_issues.view'])
        <details class="nav-group" {{ request()->routeIs('admin.cards.*', 'admin.card-*') ? 'open' : '' }}>
            <summary title="卡密管理">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6.5h16v11H4z"></path><path d="M8 6.5v11M16 6.5v11M4 10h16"></path>
                </svg>
                <strong>卡密管理</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('cards.view')
                    <a class="{{ request()->routeIs('admin.cards.index') ? 'is-active' : '' }}"
                       href="{{ route('admin.cards.index', ['adminPath' => $adminPath]) }}">卡密列表</a>
                @endcan
                @can('cards.import')<a href="#cards">卡密批量维护</a>@endcan
                @can('cards.issue')
                    <a class="{{ request()->routeIs('admin.card-issues.*') ? 'is-active' : '' }}"
                       href="{{ route('admin.card-issues.index', ['adminPath' => $adminPath]) }}">手动发卡</a>
                @endcan
                @can('card_issues.view')
                    <a href="{{ route('admin.card-issues.index', ['adminPath' => $adminPath]) }}#records">发卡记录</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['users.view', 'agents.view', 'users.create', 'users.manage'])
        <details class="nav-group">
            <summary title="账号管理">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="9" cy="8" r="4"></circle>
                    <path d="M2.5 21c.4-4.3 2.7-6.5 6.5-6.5s6.1 2.2 6.5 6.5"></path>
                    <path d="M15.5 5.2a3.7 3.7 0 0 1 0 6.8M17 14.5c2.8.6 4.3 2.8 4.5 6.5"></path>
                </svg>
                <strong>账号管理</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('users.view')<a href="#accounts">用户账号管理</a>@endcan
                @can('agents.view')<a href="#agents">代理账号管理</a>@endcan
                @can('users.create')<a href="#accounts">添加账号</a>@endcan
                @can('users.manage')<a href="#accounts">批量维护</a>@endcan
            </div>
        </details>
    @endcanany

    @canany(['settings.view', 'settings.appearance.update', 'permissions.manage', 'devices.unbind', 'legacy.manage'])
        <details class="nav-group">
            <summary title="系统配置">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"></path>
                    <path d="m4 7.5 8 4.5 8-4.5M12 12v9"></path>
                </svg>
                <strong>系统配置</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('settings.view')<a href="#settings">系统信息设置</a>@endcan
                @can('settings.appearance.update')<a href="#settings">个性化设置</a>@endcan
                @can('permissions.manage')<a href="#permissions">角色与权限</a>@endcan
                @can('devices.unbind')<a href="#devices">设备解绑</a>@endcan
                @can('legacy.manage')<a href="#api">API 与 Legacy 配置</a>@endcan
            </div>
        </details>
    @endcanany
</nav>
