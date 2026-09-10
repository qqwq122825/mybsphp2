@extends('admin.layout')

@section('title', '软件管理')

@push('head')
    @vite('resources/js/admin-ui.js')
@endpush

@section('content')
    <div data-element-software>
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">SOFTWARE MANAGEMENT</p>
            <h1>软件管理</h1>
            <p>集中查看软件模式、卡密数据和运行状态。</p>
        </div>
        @can('applications.create')
            <a class="button button-primary" href="{{ route('admin.applications.create', ['adminPath' => $adminPath]) }}">＋ 添加新软件</a>
        @endcan
    </header>

    <section class="metric-grid">
        <article class="metric-card metric-blue"><span>软件总数</span><strong>{{ $metrics['total'] }}</strong><small>当前后台全部软件</small></article>
        <article class="metric-card metric-green"><span>正常开放</span><strong>{{ $metrics['active'] }}</strong><small>客户端可正常访问</small></article>
        <article class="metric-card metric-orange"><span>卡密验证</span><strong>{{ $metrics['card_login'] }}</strong><small>卡密直接登录</small></article>
        <article class="metric-card metric-red"><span>账户模式</span><strong>{{ $metrics['account_recharge'] }}</strong><small>注册账户并充值</small></article>
    </section>

    @if ($applications->isEmpty())
        <section class="dashboard-panel data-panel">
            <div class="empty-activity">
                <b>◫</b><strong>还没有软件</strong><p>先添加软件，再建立卡种并生成卡密。</p>
            </div>
        </section>
    @else
        <section class="software-card-list" aria-label="软件列表">
            @foreach ($applications as $application)
                @php($notActivated = max(0, $application->cards_count - $application->activated_cards_count))
                <article class="software-card">
                    <header>
                        <div class="software-card-title">
                            <b>◫</b>
                            <div>
                                <h2>{{ $application->name }}</h2>
                                <p>代号：<strong>{{ $application->code }}</strong></p>
                            </div>
                        </div>
                        <div class="software-card-header-actions">
                            <span class="status-tag status-{{ $application->status }}">
                                {{ $application->status === 'active' ? '正常开放' : '停止使用' }}
                            </span>
                            @can('applications.delete')
                                <button type="button" class="software-delete-trigger"
                                        data-window-open="delete-application-{{ $application->id }}">▥ 删除软件</button>
                            @endcan
                        </div>
                    </header>

                    <div class="software-mode-line">
                        <span>软件模式</span>
                        <strong>{{ $application->mode === 'card_login' ? '卡串验证限时模式' : '账号登录限时模式' }}</strong>
                    </div>

                    <div class="software-stats">
                        <div><span>用户总数</span><strong>{{ $application->mode === 'card_login' ? $application->card_users_count : $application->accounts_count }}</strong></div>
                        <div><span>卡总数</span><strong>{{ $application->cards_count }}</strong></div>
                        <div><span>卡已激活</span><strong>{{ $application->activated_cards_count }}</strong></div>
                        <div><span>卡未激活</span><strong>{{ $notActivated }}</strong></div>
                        <div><span>卡类型数量</span><strong>{{ $application->card_types_count }}</strong></div>
                    </div>

                    <footer class="software-card-actions">
                        @can('applications.update')
                            <a class="software-action-primary"
                               href="{{ route('admin.applications.edit', ['adminPath' => $adminPath, 'application' => $application]) }}"
                               data-workspace-open data-workspace-title="{{ $application->name }} · 软件配置">⚙ 软件配置</a>
                        @endcan
                        @can('risk.view')
                            <a class="software-action-risk"
                               href="{{ route('admin.risk-control.index', ['adminPath' => $adminPath, 'application' => $application->public_id]) }}"
                               data-workspace-open data-workspace-title="{{ $application->name }} · 封控设置">◇ 封控设置</a>
                        @endcan
                        @can('applications.view')
                            <a href="{{ route('admin.applications.users.index', ['adminPath' => $adminPath, 'application' => $application]) }}"
                               data-workspace-open data-workspace-title="{{ $application->name }} · 用户管理">▣ 用户管理</a>
                        @endcan
                        @can('cards.view')
                            <a href="{{ route('admin.cards.index', ['adminPath' => $adminPath, 'application' => $application->getKey()]) }}"
                               data-workspace-open data-workspace-title="{{ $application->name }} · 充值卡列表">▣ 充值卡列表</a>
                        @endcan
                        @can('card_types.manage')
                            <a href="{{ route('admin.software-card-types.index', ['adminPath' => $adminPath, 'application' => $application]) }}"
                               data-workspace-open data-workspace-title="{{ $application->name }} · 卡串类型">☷ 卡串类型</a>
                        @endcan
                        @can('batches.create')
                            <a class="software-action-warn" href="{{ route('admin.software-card-making.show', ['adminPath' => $adminPath, 'application' => $application]) }}"
                               data-workspace-open data-workspace-title="{{ $application->name }} · 现卡制作">＋ 现卡制作</a>
                        @endcan
                    </footer>
                </article>
                @can('applications.delete')
                    <x-admin.window-dialog class="application-delete-dialog" id="delete-application-{{ $application->id }}"
                                           title="删除软件 - 代号：{{ $application->code }}"
                                           :workspace="true" :minimizable="false">
                        <form method="post" action="{{ route('admin.applications.destroy', ['adminPath' => $adminPath, 'application' => $application]) }}">
                            @csrf
                            @method('DELETE')
                            <div class="application-delete-dialog-body">
                                <x-admin.delete-option label="当前软件" name="delete_application" description="勾选后删除软件本身；不勾选时软件继续保留。" :master="true" />
                                <x-admin.delete-option label="软件下用户" name="delete_users" description="同时清理当前软件下的账号用户。" />
                                <x-admin.delete-option label="当前软件卡" name="delete_cards" description="只勾选此项时，仅清理充值卡和制卡批次，软件继续保留。" />
                                <x-admin.delete-option label="当前软件分类" name="delete_card_types" description="同时清理当前软件的充值卡类型。" />
                                <p><strong>说明：</strong>由于软件存在关联数据，请按需要选择删除内容。勾选“当前软件”时会自动全选。</p>
                            </div>
                            <footer>
                                <button type="button" onclick="this.closest('dialog').close()">返回列表</button>
                                <button type="submit" class="button-danger">确认删除</button>
                            </footer>
                        </form>
                    </x-admin.window-dialog>
                @endcan
            @endforeach
        </section>

        <div class="pager software-pager" data-page="{{ $applications->currentPage() }}" data-total="{{ $applications->total() }}" data-per-page="{{ $applications->perPage() }}">
            @if ($applications->previousPageUrl())<a href="{{ $applications->previousPageUrl() }}">上一页</a>@endif
            <span>第 {{ $applications->currentPage() }} / {{ $applications->lastPage() }} 页</span>
            @if ($applications->nextPageUrl())<a href="{{ $applications->nextPageUrl() }}">下一页</a>@endif
        </div>
    @endif
    </div>
@endsection
