<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3867e8">
    <title>管理员登录 · {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css?v=20260724-compact-login-v2">
    <script src="/assets/js/mybsphp.js" defer></script>
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-visual" aria-label="系统介绍">
        <div class="login-message">
            <strong class="login-wordmark">BSPH</strong>
            <h1>后台管理系统</h1>
            <p>请输入管理员账号、密码与安全码进行登录。</p>
        </div>
    </section>

    <section class="login-form-panel">
        <div class="login-form-box">
            <span class="mobile-brand">BSPH · {{ $appName }}</span>
            <header>
                <h1>管理员登录</h1>
                <p>请确认账号信息后进入后台</p>
            </header>

            @if ($errors->any())
                <div class="form-alert" role="alert" tabindex="-1" data-error-summary>
                    <span aria-hidden="true">!</span>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="post" action="{{ route('admin.login', ['adminPath' => $adminPath]) }}"
                  class="login-form" data-submit-form>
                @csrf
                <label class="field">
                    <span>管理员账号</span>
                    <input name="username" value="{{ old('username') }}" placeholder="请输入管理员账号"
                           required autofocus autocomplete="username">
                </label>
                <label class="field">
                    <span>密码</span>
                    <input name="password" id="login-password" type="password" placeholder="请输入密码"
                           required autocomplete="current-password">
                </label>
                <label class="field">
                    <span>安全码 <small>（二级密码）</small></span>
                    <input name="secondary_password" id="login-secondary-password" type="password"
                           placeholder="未启用可留空" autocomplete="off">
                </label>
                <button class="button button-primary login-button" type="submit" data-submit-label="正在登录…">
                    登录
                </button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
