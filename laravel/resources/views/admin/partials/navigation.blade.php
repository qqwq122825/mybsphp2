<nav aria-label="管理后台导航">
    @canany(['dashboard.view', 'online_users.view', 'audit.view', 'administrators.view', 'administrators.create'])
        <details class="nav-group" {{ request()->routeIs('admin.dashboard', 'admin.system-logs.*', 'admin.administrators.*') ? 'open' : '' }}>
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
                @can('audit.view')
                    <details class="nav-subgroup" {{ request()->routeIs('admin.system-logs.*') ? 'open' : '' }}>
                        <summary>系统日志<i aria-hidden="true"></i></summary>
                        <div>
                            <a class="{{ request()->routeIs('admin.system-logs.index') && request()->route('type') === 'login' ? 'is-active' : '' }}" href="{{ route('admin.system-logs.index',['adminPath'=>$adminPath,'type'=>'login']) }}">管理登录日志</a>
                            <a class="{{ request()->routeIs('admin.system-logs.index') && request()->route('type') === 'card-making' ? 'is-active' : '' }}" href="{{ route('admin.system-logs.index',['adminPath'=>$adminPath,'type'=>'card-making']) }}">系统制卡日志</a>
                            <a class="{{ request()->routeIs('admin.system-logs.index') && request()->route('type') === 'protection' ? 'is-active' : '' }}" href="{{ route('admin.system-logs.index',['adminPath'=>$adminPath,'type'=>'protection']) }}">系统安全防护日志</a>
                        </div>
                    </details>
                @endcan
                @canany(['administrators.view', 'administrators.create'])
                    <details class="nav-subgroup" {{ request()->routeIs('admin.administrators.*') ? 'open' : '' }}>
                        <summary>后台管理员<i aria-hidden="true"></i></summary>
                        <div>
                            @can('administrators.view')
                                <a class="{{ request()->routeIs('admin.administrators.index') ? 'is-active' : '' }}"
                                   href="{{ route('admin.administrators.index', ['adminPath' => $adminPath]) }}">管理员列表</a>
                            @endcan
                            @can('administrators.create')
                                <a class="{{ request()->routeIs('admin.administrators.create') ? 'is-active' : '' }}"
                                   href="{{ route('admin.administrators.create', ['adminPath' => $adminPath]) }}">添加管理员</a>
                            @endcan
                        </div>
                    </details>
                @endcanany
            </div>
        </details>
    @endcanany

    @canany(['applications.view', 'applications.create', 'cards.bulk_manage', 'app_accounts.manage'])
        <details class="nav-group" {{ request()->routeIs('admin.applications.*', 'admin.cards.maintenance.*', 'admin.application-users.maintenance.*') ? 'open' : '' }}>
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
                @can('cards.bulk_manage')
                    <a class="{{ request()->routeIs('admin.cards.maintenance.*') ? 'is-active' : '' }}"
                       href="{{ route('admin.cards.maintenance.index', ['adminPath' => $adminPath]) }}">充值卡批量维护</a>
                @endcan
                @can('app_accounts.manage')
                    <a class="{{ request()->routeIs('admin.application-users.maintenance.*') ? 'is-active' : '' }}"
                       href="{{ route('admin.application-users.maintenance.index', ['adminPath' => $adminPath]) }}">软件用户批量维护</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['agents.view', 'users.create', 'users.manage'])
        <details class="nav-group" {{ request()->routeIs('admin.agents.*') ? 'open' : '' }}>
            <summary title="账号管理">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="9" cy="8" r="4"></circle>
                    <path d="M2.5 21c.4-4.3 2.7-6.5 6.5-6.5s6.1 2.2 6.5 6.5"></path>
                    <path d="M15.5 5.2a3.7 3.7 0 0 1 0 6.8M17 14.5c2.8.6 4.3 2.8 4.5 6.5"></path>
                </svg>
                <strong>账号管理</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('agents.view')
                    <a class="{{ request()->routeIs('admin.agents.index') ? 'is-active' : '' }}"
                       href="{{ route('admin.agents.index', ['adminPath' => $adminPath]) }}">代理账号管理</a>
                @endcan
                @can('users.create')
                    <a class="{{ request()->routeIs('admin.agents.create') ? 'is-active' : '' }}"
                       href="{{ route('admin.agents.create', ['adminPath' => $adminPath]) }}">添加代理账号</a>
                @endcan
            </div>
        </details>
    @endcanany

    @can('settings.view')
        <details class="nav-group" {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}>
            <summary title="系统配置">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"></path>
                    <path d="m4 7.5 8 4.5 8-4.5M12 12v9"></path>
                </svg>
                <strong>系统配置</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                <a class="{{ request()->routeIs('admin.settings.system.*') ? 'is-active' : '' }}"
                   href="{{ route('admin.settings.system.index', ['adminPath' => $adminPath]) }}">系统信息设置</a>
            </div>
        </details>
    @endcan

    @canany(['audit.view', 'cards.view', 'cards.bulk_manage', 'app_accounts.manage'])
        <details class="nav-group" {{ request()->routeIs('admin.operation-plugins.*') ? 'open' : '' }}>
            <summary title="运营插件">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"></path>
                </svg>
                <strong>运营插件</strong><i aria-hidden="true"></i>
            </summary>
            <div class="nav-children">
                @can('audit.view')
                    <a class="{{ request()->route('tool') === 'monitor' ? 'is-active' : '' }}" href="{{ route('admin.operation-plugins.show', ['adminPath' => $adminPath, 'tool' => 'monitor']) }}">接口监控调试插件</a>
                @endcan
                @can('cards.view')
                    <a class="{{ request()->route('tool') === 'batch-query' ? 'is-active' : '' }}" href="{{ route('admin.operation-plugins.show', ['adminPath' => $adminPath, 'tool' => 'batch-query']) }}">批量查询</a>
                @endcan
                @can('cards.bulk_manage')
                    <a class="{{ request()->route('tool') === 'batch-freeze' ? 'is-active' : '' }}" href="{{ route('admin.operation-plugins.show', ['adminPath' => $adminPath, 'tool' => 'batch-freeze']) }}">批量封卡</a>
                    <a class="{{ request()->route('tool') === 'batch-unfreeze' ? 'is-active' : '' }}" href="{{ route('admin.operation-plugins.show', ['adminPath' => $adminPath, 'tool' => 'batch-unfreeze']) }}">批量解冻</a>
                    <a class="{{ request()->route('tool') === 'batch-delete' ? 'is-active' : '' }}" href="{{ route('admin.operation-plugins.show', ['adminPath' => $adminPath, 'tool' => 'batch-delete']) }}">批量删除</a>
                @endcan
            </div>
        </details>
    @endcanany
</nav>
