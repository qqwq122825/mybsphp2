@extends('admin.layout')

@section('title', '后台管理员')

@section('content')
    <header class="page-heading">
        <div>
            <p class="eyebrow">ADMINISTRATORS</p>
            <h1>后台管理员</h1>
            <p>创建管理员或代理后台账号，并使用系统角色控制菜单和操作权限。</p>
        </div>
    </header>

    <section class="management-grid">
        @can('administrators.create')
            <form class="dashboard-panel form-panel" method="post" action="{{ route('admin.administrators.store', ['adminPath' => $adminPath]) }}">
                @csrf
                <header><div><span>NEW ACCOUNT</span><h2>创建后台账号</h2></div><em>密码至少 6 位</em></header>
                <div class="panel-body stack-form compact-form">
                    <label class="field"><span>登录账号</span><input name="username" minlength="3" maxlength="32" required></label>
                    <div class="field-grid">
                        <label class="field"><span>登录密码</span><input type="password" name="password" minlength="6" required></label>
                        <label class="field"><span>确认密码</span><input type="password" name="password_confirmation" minlength="6" required></label>
                    </div>
                    <div class="field-grid">
                        <label class="field"><span>二级密码（可选）</span><input type="password" name="secondary_password" minlength="6"></label>
                        <label class="field"><span>确认二级密码</span><input type="password" name="secondary_password_confirmation" minlength="6"></label>
                    </div>
                    <label class="field"><span>系统角色</span>
                        <select name="role_code" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->code }}">{{ $role->name }} · {{ $role->description }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="info-panel"><span>i</span><p>代理角色默认没有删卡、作废任意卡密、设备解绑、软件账户管理和角色权限管理能力。</p></div>
                    <button class="button button-primary" type="submit">创建后台账号</button>
                </div>
            </form>
        @endcan

        <section class="dashboard-panel status-panel">
            <header><div><span>ROLE BASELINE</span><h2>角色基线</h2></div><em class="ok-badge">默认拒绝</em></header>
            <dl>
                <div><dt>超级管理员</dt><dd>全部权限</dd></div>
                <div><dt>管理员</dt><dd>运营管理，排除删卡/删管理员</dd></div>
                <div><dt>代理</dt><dd>应用、库存和自身发卡</dd></div>
                <div><dt>权限来源</dt><dd>Gate + Policy</dd></div>
            </dl>
        </section>
    </section>

    <section class="dashboard-panel data-panel">
        <header><div><span>ACCOUNT LIST</span><h2>账号列表</h2></div><em>{{ $administrators->total() }} 个</em></header>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>账号</th><th>角色</th><th>状态</th><th>二级密码</th><th>最近登录</th><th>维护</th></tr></thead>
                <tbody>
                @foreach($administrators as $managed)
                    <tr>
                        <td><strong>{{ $managed->username }}</strong><small>#{{ $managed->id }}</small></td>
                        <td>{{ $managed->roles->pluck('name')->join('、') ?: $managed->role }}</td>
                        <td><span class="status-tag status-{{ $managed->status }}">{{ $managed->status }}</span></td>
                        <td>{{ $managed->secondary_password_required ? '已启用' : '关闭' }}</td>
                        <td>{{ $managed->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @can('administrators.update')
                                @if(!$managed->is($administrator))
                                    <form class="inline-admin-form" method="post"
                                          action="{{ route('admin.administrators.update', ['adminPath' => $adminPath, 'managedAdministrator' => $managed]) }}">
                                        @csrf @method('PUT')
                                        <select name="role_code">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->code }}" @selected($managed->roles->contains('code', $role->code))>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="status">
                                            <option value="active" @selected($managed->status === 'active')>启用</option>
                                            <option value="frozen" @selected($managed->status === 'frozen')>冻结</option>
                                        </select>
                                        <button type="submit">保存</button>
                                    </form>
                                @else
                                    <small>当前账号</small>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
