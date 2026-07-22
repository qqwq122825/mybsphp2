@extends('admin.layout')

@section('title', '管理首页')

@section('content')
<div class="bsphp-home">
    <section class="dashboard-panel bsphp-stat-section">
        <header><h2>系统基本信息 <small>以下数据来自当前数据库</small></h2></header>
        <div class="bsphp-stat-grid basic-grid">
            @foreach([
                ['账号总数', $business['account_total'].'个'], ['今天登录', $business['today_login'].'个'],
                ['代理总数', $business['agent_total'].'个'], ['今天注册数量', $business['today_registered'].'个'],
                ['总用户余额', $business['balance_yuan'].'元'], ['今天激活注册', $business['today_activated'].'个'],
                ['在线用户(最近30分钟活动)', $business['online_users'].'人'], ['软件总数', $business['software_total'].'个']
            ] as [$label,$value])
                <article><span>{{ $label }}</span><strong>{{ $value }}</strong></article>
            @endforeach
        </div>
    </section>

    <section class="dashboard-panel bsphp-stat-section">
        <header><h2>系统消费信息 <small>充值卡和消费数据</small></h2></header>
        <div class="bsphp-stat-grid consume-grid">
            @foreach([
                ['总充值卡',$business['cards_total'].'张'], ['总未激活卡',$business['cards_unused'].'张'],
                ['总已激活卡',$business['cards_activated'].'张'], ['今天激活卡',$business['today_cards'].'张'],
                ['今天消费',$business['today_consumption'].'元'], ['今天充值',$business['today_recharge'].'元']
            ] as [$label,$value])
                <article><span>{{ $label }}</span><strong>{{ $value }}</strong></article>
            @endforeach
        </div>
    </section>

    <section class="dashboard-panel bsphp-stat-section detection-section">
        <header><h2>系统检测信息 <small>安全日志与 API 异常统计</small></h2></header>
        <div class="bsphp-stat-grid detection-grid">
            @foreach([
                ['今天系统安全防护日志',$business['security_today'].'条'], ['最近7天系统安全防护日志',$business['security_week'].'条'],
                ['今日API接口空异常',$business['api_today'].'条'], ['最近7天API接口空异常',$business['api_week'].'条']
            ] as [$label,$value])
                <article><span>{{ $label }}</span><strong>{{ $value }}</strong></article>
            @endforeach
        </div>
    </section>

    <section class="dashboard-panel bsphp-apps-section">
        <header><h2>我的全部软件</h2></header>
        <x-admin.table-frame frame-class="bsphp-home-table-component">
            <table><thead><tr><th>软件代号</th><th>名称</th><th>登录验证模式</th><th>软件用户数</th><th>激活码(卡)</th><th>库卡</th><th>操作</th></tr></thead>
                <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>{{ $application->code }}</td><td>{{ $application->name }}</td>
                        <td>{{ $application->mode === 'account_recharge' ? '账号登录限时模式' : '卡串验证限时模式' }}</td>
                        <td>{{ $application->mode === 'account_recharge' ? $application->accounts_count : $application->activated_cards_count }}个</td>
                        <td>{{ $application->activated_cards_count }}张</td><td>{{ $application->inventory_cards_count }}张</td>
                        <td><div class="bsphp-row-actions">
                            @can('applications.update')<a href="{{ route('admin.applications.edit',['adminPath'=>$adminPath,'application'=>$application]) }}">配置软件</a>@endcan
                            @can('batches.create')<a href="{{ route('admin.software-card-making.show',['adminPath'=>$adminPath,'application'=>$application]) }}">制作充值卡</a>@endcan
                            @can('applications.view')<a href="{{ route('admin.applications.users.index',['adminPath'=>$adminPath,'application'=>$application]) }}">更多..</a>@endcan
                        </div></td>
                    </tr>
                @empty<tr><td colspan="7" class="empty-cell">暂无软件数据。</td></tr>@endforelse
                </tbody>
            </table>
            <x-slot:footer><span>共 {{ $applications->count() }} 个软件</span></x-slot:footer>
        </x-admin.table-frame>
    </section>

    <section class="bsphp-home-bottom">
        <article class="dashboard-panel environment-info">
            <header><h2>系统环境信息</h2></header>
            <dl>
                <div><dt>当前服务系统时间：</dt><dd>{{ now()->format('Y-m-d H:i:s') }}（如果时间不对，请调整系统时差）</dd></div>
                <div><dt>当前登录账号：</dt><dd>{{ $administrator->username }}</dd></div>
                <div><dt>上次登陆时间：</dt><dd>{{ $administrator->last_login_at?->format('Y-m-d H:i:s') ?? '首次登录' }}</dd></div>
                <div><dt>数据库连接：</dt><dd>{{ $databaseHealthy ? '连接正常' : '连接异常' }}</dd></div>
                <div><dt>系统时区：</dt><dd>{{ config('app.timezone') }}</dd></div>
                <div><dt>HTTP协议版本：</dt><dd>{{ request()->server('SERVER_PROTOCOL','HTTP/1.1') }}</dd></div>
            </dl>
        </article>
        <div class="bsphp-home-side">
            <article class="dashboard-panel version-info"><header><h2>系统版本信息</h2></header><dl>
                <div><dt>系统版本</dt><dd>MyBsPHP2</dd></div><div><dt>Laravel</dt><dd>{{ app()->version() }}</dd></div>
                <div><dt>PHP</dt><dd>{{ PHP_VERSION }}</dd></div><div><dt>运行状态</dt><dd class="status-ok">正常</dd></div>
            </dl></article>
            <article class="dashboard-panel announcement"><header><h2>MyBsPHP2 系统公告</h2></header><p>请使用复杂的后台密码，并定期备份数据。<br>软件、卡密和用户数据均来自当前数据库。</p></article>
        </div>
    </section>
</div>
@endsection
