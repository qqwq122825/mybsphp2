<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3867e8">
    <title>管理员登录 · {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css">
    <script src="/assets/js/mybsphp.js" defer></script>
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-visual" aria-label="系统介绍">
        <div class="login-brand">
            <span class="brand-mark">BS</span>
            <span><strong>{{ $appName }}</strong><small>OPEN LICENSE PLATFORM</small></span>
        </div>
        <div class="login-message">
            <p class="eyebrow">MYBSPHP2 ADMIN</p>
            <h1>后台管理系统</h1>
            <p>集中管理应用、卡密、代理、设备与风险事件。</p>
        </div>
        <p class="login-visual-foot"><span></span>清晰、稳定、适配手机与电脑</p>
    </section>

    <section class="login-form-panel">
        <div class="login-form-box">
            <span class="mobile-brand">{{ $appName }}</span>
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
                    <span class="password-input">
                        <input name="password" id="login-password" type="password" placeholder="请输入密码"
                               required autocomplete="current-password">
                        <button type="button" data-password-toggle="login-password" aria-pressed="false">显示</button>
                    </span>
                </label>
                @if ($secondaryPasswordEnabled)
                    <label class="field">
                        <span>二级密码</span>
                        <span class="password-input">
                            <input name="secondary_password" id="login-secondary-password" type="password"
                                   placeholder="请输入二级密码" autocomplete="off">
                            <button type="button" data-password-toggle="login-secondary-password" aria-pressed="false">显示</button>
                        </span>
                    </label>
                @endif
                <button class="button button-primary login-button" type="submit" data-submit-label="正在登录…">
                    登录 <span aria-hidden="true">→</span>
                </button>
            </form>

            <footer class="login-security"><span></span>登录已启用频率限制、会话轮换与审计保护</footer>
        </div>
    </section>
</main>
</body>
</html>
