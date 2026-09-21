@extends('admin.layout')
@section('title', '个人安全设置')
@section('content')
<section class="dashboard-panel administrator-create-panel" id="current-security">
    <header><div><h2>个人安全设置</h2><small>仅允许单端登录：新登录会使旧端失效，保存安全设置后请重新登录。</small></div></header>
    <form method="post" action="{{ route('admin.administrators.security.update', ['adminPath' => $adminPath]) }}">
        @csrf @method('PUT')
        <div class="administrator-create-rows">
            <label class="administrator-create-row"><strong>管理员用户名</strong><input name="username" value="{{ old('username', $administrator->username) }}" required minlength="3" maxlength="32" pattern="[A-Za-z][A-Za-z0-9_-]+" autocomplete="username"><small>3–32位，字母开头</small></label>
            <label class="administrator-create-row"><strong>当前登录密码</strong><input type="password" name="current_password" required maxlength="128" autocomplete="current-password"><small>修改前验证身份</small></label>
            @if($administrator->secondary_password_required)
            <label class="administrator-create-row"><strong>当前二级密码</strong><input type="password" name="current_secondary_password" required maxlength="128" autocomplete="off"><small>已启用二级密码，修改时须验证</small></label>
            @endif
            <label class="administrator-create-row"><strong>新登录密码</strong><input type="password" name="password" minlength="6" maxlength="128" autocomplete="new-password"><small>留空保持原密码</small></label>
            <label class="administrator-create-row"><strong>确认新登录密码</strong><input type="password" name="password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"></label>
            <label class="administrator-create-row"><strong>二级密码设置</strong><select name="secondary_action"><option value="keep">保持原设置（{{ $administrator->secondary_password_required ? '已启用' : '关闭' }}）</option><option value="enable" @selected(old('secondary_action') === 'enable')>启用或更换</option><option value="disable" @selected(old('secondary_action') === 'disable')>关闭</option></select><small>启用或更换时请填写下面两个输入框</small></label>
            <label class="administrator-create-row"><strong>新二级密码</strong><input type="password" name="secondary_password" minlength="6" maxlength="128" autocomplete="new-password"><small>登录时额外验证，须与登录密码不同</small></label>
            <label class="administrator-create-row"><strong>确认新二级密码</strong><input type="password" name="secondary_password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"></label>
            <div class="administrator-create-actions"><button class="button button-primary" type="submit">保存并重新登录</button></div>
        </div>
    </form>
</section>
@endsection
