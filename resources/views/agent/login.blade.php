<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3867e8">
    <title>代理商管理平台登录 - {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css?v=20260725-custom-select">
    <link rel="stylesheet" href="/assets/css/agent.css?v=20260725-custom-select">
    <script src="/assets/js/mybsphp.js?v=20260725-custom-select" defer></script>
</head>
<body class="agent-login-page">
<main class="agent-login-card">
    <section class="agent-login-brand" aria-label="平台介绍">
        <div class="agent-brand-copy">
            <strong>BSPH</strong>
            <h2>欢迎登录代理商管理平台</h2>
            <p>请使用代理账号完成身份验证后进入系统。</p>
        </div>
        <div class="agent-brand-wave" aria-hidden="true"></div>
    </section>
    <section class="agent-login-form-panel">
        <div class="agent-login-form-box">
            <span class="agent-mobile-brand">BSPH · {{ $appName }}</span>
            <header>
                <h1>代理商管理平台登录</h1>
                <p>请输入代理账号信息</p>
            </header>

            @if($errors->any())
                <div class="agent-form-alert">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('agent.login.store') }}" class="agent-login-form">
                @csrf
                <label><span>用户名</span><input name="username" value="{{ old('username') }}" autocomplete="username" required autofocus></label>
                <label><span>密码</span><input type="password" name="password" autocomplete="current-password" required></label>
                @if($languageEnabled)
                <label><span>语言</span><select name="language"><option value="zh-CN">简体中文</option></select></label>
                @endif
                @if($captchaEnabled)
                <label>
                    <span>图形验证码</span>
                    <div class="agent-captcha-row">
                        <input name="captcha" maxlength="4" inputmode="numeric" autocomplete="off" placeholder="请输入验证码" required>
                        <button type="button" class="agent-captcha-code" title="点击刷新验证码" onclick="window.location.reload()">{{ $captcha }}</button>
                    </div>
                </label>
                @endif
                <button class="agent-login-submit" type="submit">登入</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
