@extends('admin.layout')

@section('title', '应用管理')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">APPLICATIONS</p>
            <h1>应用管理</h1>
            <p>管理卡密登录和账户充值两种授权模式。</p>
        </div>
        @can('applications.create')
            <a class="button button-primary" href="{{ route('admin.applications.create', ['adminPath' => $adminPath]) }}">＋ 添加应用</a>
        @endcan
    </header>

    <section class="metric-grid">
        <article class="metric-card metric-blue"><span>应用总数</span><strong>{{ $metrics['total'] }}</strong><small>包含草稿与停用</small></article>
        <article class="metric-card metric-green"><span>已启用</span><strong>{{ $metrics['active'] }}</strong><small>客户端可正常访问</small></article>
        <article class="metric-card metric-orange"><span>卡密登录</span><strong>{{ $metrics['card_login'] }}</strong><small>一次性卡密登录模式</small></article>
        <article class="metric-card metric-red"><span>账户充值</span><strong>{{ $metrics['account_recharge'] }}</strong><small>账号注册与充值模式</small></article>
    </section>

    <section class="dashboard-panel data-panel">
        <header>
            <div><span>APPLICATION LIST</span><h2>软件列表</h2></div>
            <em>{{ $applications->total() }} 个应用</em>
        </header>
        @if ($applications->isEmpty())
            <div class="empty-activity">
                <b>◫</b><strong>还没有应用</strong><p>先创建应用，再建立卡种和卡密批次。</p>
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>应用</th><th>模式</th><th>状态</th><th>卡种</th><th>卡密</th><th>版本</th><th>操作</th></tr></thead>
                    <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td>
                                <strong>{{ $application->name }}</strong>
                                <small>{{ $application->code }} · {{ $application->public_id }}</small>
                            </td>
                            <td><span class="tag">{{ $application->mode === 'card_login' ? '卡密登录' : '账户充值' }}</span></td>
                            <td><span class="status-tag status-{{ $application->status }}">{{ $application->status }}</span></td>
                            <td>{{ $application->card_types_count }}</td>
                            <td>{{ $application->cards_count }}</td>
                            <td>{{ $application->version_name ?: '—' }}</td>
                            <td class="row-actions">
                                @can('applications.update')
                                    <a href="{{ route('admin.applications.edit', ['adminPath' => $adminPath, 'application' => $application]) }}">配置</a>
                                @endcan
                                @can('applications.status.update')
                                    <form method="post" action="{{ route('admin.applications.toggle', ['adminPath' => $adminPath, 'application' => $application]) }}">
                                        @csrf
                                        <button type="submit">{{ $application->status === 'active' ? '停用' : '启用' }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pager">
                @if ($applications->previousPageUrl())<a href="{{ $applications->previousPageUrl() }}">上一页</a>@endif
                <span>第 {{ $applications->currentPage() }} / {{ $applications->lastPage() }} 页</span>
                @if ($applications->nextPageUrl())<a href="{{ $applications->nextPageUrl() }}">下一页</a>@endif
            </div>
        @endif
    </section>
@endsection
