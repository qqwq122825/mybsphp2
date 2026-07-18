<?php
declare(strict_types=1);
/** @var string $appName */
/** @var string $csrfToken */
/** @var list<string> $errors */
/** @var string $username */
/** @var bool $secondaryPasswordEnabled */
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#3867e8">
    <title>管理员登录 · <?= htmlspecialchars((string) $appName) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual" aria-label="系统介绍">
        <div class="auth-visual__glow auth-visual__glow--one" aria-hidden="true"></div>
        <div class="auth-visual__glow auth-visual__glow--two" aria-hidden="true"></div>
        <div class="auth-brand">
            <span class="auth-brand__mark">BS</span>
            <span>
                <strong>BSPH</strong>
                <small>OPEN LICENSE PLATFORM</small>
            </span>
        </div>
        <div class="auth-visual__content">
            <span class="auth-kicker">MYBSPHP2 ADMIN</span>
            <h1>后台管理系统</h1>
            <p>集中管理应用、卡密、代理、设备与风险事件。</p>
        </div>
        <div class="auth-visual__footer">
            <span></span>
            <p>清晰、稳定、适配手机与电脑</p>
        </div>
    </section>

    <section class="auth-form-panel" aria-labelledby="login-title">
        <div class="auth-form-box">
            <header class="auth-form-header">
                <span class="auth-mobile-brand">BSPH</span>
                <h2 id="login-title">管理员登录</h2>
                <p>请确认账号信息后进入后台</p>
            </header>

            <?php if ($errors !== []): ?>
                <div class="auth-alert" role="alert">
                    <span aria-hidden="true">!</span>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" autocomplete="on">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <label class="form-field">
                    <span>管理员账号</span>
                    <input name="username" value="<?= htmlspecialchars($username) ?>"
                           placeholder="请输入管理员账号" required autofocus autocomplete="username">
                </label>

                <label class="form-field">
                    <span>密码</span>
                    <input type="password" name="password" placeholder="请输入密码"
                           required autocomplete="current-password">
                </label>

                <?php if ($secondaryPasswordEnabled): ?>
                    <label class="form-field">
                        <span>二级密码</span>
                        <input type="password" name="secondary_password" placeholder="请输入二级密码"
                               required autocomplete="off">
                    </label>
                <?php endif; ?>

                <button class="primary-button auth-submit" type="submit">
                    登录
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            <footer class="auth-security">
                <span class="status-dot" aria-hidden="true"></span>
                登录已启用频率限制和会话保护
            </footer>
        </div>
    </section>
</main>
</body>
</html>
