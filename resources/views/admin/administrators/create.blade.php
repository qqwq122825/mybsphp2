@extends('admin.layout')

@section('title', '添加管理员')

@section('content')
<section class="dashboard-panel administrator-create-panel">
    <header>
        <div>
            <h2>添加管理员</h2>
            <small>创建新的后台登录账号并设置角色权限</small>
        </div>
        <a class="button button-secondary" href="{{ route('admin.administrators.index', ['adminPath' => $adminPath]) }}">返回管理员列表</a>
    </header>

    <form method="post" action="{{ route('admin.administrators.store', ['adminPath' => $adminPath]) }}">
        @csrf
        <div class="administrator-create-rows">
            <label class="administrator-create-row">
                <strong>管理员账号</strong>
                <input name="username" value="{{ old('username') }}" minlength="3" maxlength="32" required
                       autocomplete="username" placeholder="3-32位，字母开头">
                <small>后台登录账号，可使用字母、数字、下划线和短横线</small>
            </label>

            <label class="administrator-create-row">
                <strong>登录密码</strong>
                <input type="password" name="password" minlength="6" maxlength="128" required
                       autocomplete="new-password" placeholder="至少6位">
                <small>管理员登录密码，建议同时包含字母和数字</small>
            </label>

            <label class="administrator-create-row">
                <strong>确认密码</strong>
                <input type="password" name="password_confirmation" minlength="6" maxlength="128" required
                       autocomplete="new-password" placeholder="再次输入登录密码">
                <small>需要与登录密码保持一致</small>
            </label>

            <label class="administrator-create-row">
                <strong>二级密码</strong>
                <input type="password" name="secondary_password" minlength="6" maxlength="128"
                       autocomplete="new-password" placeholder="选填，至少6位">
                <small>登录时额外验证的独立密码，留空表示暂不开启</small>
            </label>

            <label class="administrator-create-row">
                <strong>确认二级密码</strong>
                <input type="password" name="secondary_password_confirmation" minlength="6" maxlength="128"
                       autocomplete="new-password" placeholder="再次输入二级密码">
                <small>填写二级密码时需要同时确认</small>
            </label>

            <div class="administrator-create-note">
                系统超级管理员由安装流程建立，此处仅添加普通后台管理员。
            </div>

            <div class="administrator-create-actions">
                <button class="button button-primary" type="submit">确认保存</button>
                <a class="button button-secondary" href="{{ route('admin.administrators.index', ['adminPath' => $adminPath]) }}">取消</a>
            </div>
        </div>
    </form>
</section>
@endsection
