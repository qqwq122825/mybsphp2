<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3867e8">
    <title>系统已安装 · {{ $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css">
</head>
<body class="install-page">
<main class="install-shell install-locked-shell">
    <section class="install-card install-locked-card" aria-labelledby="installed-title">
        <header class="install-topbar">
            <a class="brand" href="/">
                <span class="brand-mark">BS</span>
                <span><strong>MyBsPHP2</strong><small>开源授权管理系统</small></span>
            </a>
            <span class="step-counter is-installed">已安装</span>
        </header>

        <div class="install-content install-locked-content">
            <span class="installed-check" aria-hidden="true">✓</span>
            <header class="section-heading">
                <p class="eyebrow">INSTALLATION LOCKED</p>
                <h1 id="installed-title">系统已安装成功</h1>
                <p>安装锁正在保护当前站点。需要重新安装时，请手动删除下面的锁文件，然后重新访问 <code>/install</code>。</p>
            </header>

            <div class="install-lock-path">
                <span>需要删除的文件</span>
                <code>{{ $lockFilePath }}</code>
            </div>

            <ol class="install-lock-guide">
                <li><span>1</span><p><strong>先备份数据库</strong><small>重新安装并选择覆盖后，会清空所选数据库。</small></p></li>
                <li><span>2</span><p><strong>删除安装锁</strong><small>只需删除上面显示的文件，不需要修改 `.env`。</small></p></li>
                <li><span>3</span><p><strong>重新访问安装页</strong><small>刷新当前页面即可重新进入五步安装向导。</small></p></li>
            </ol>

            @if(is_string($installedAt) && $installedAt !== '')
                <p class="installed-at">最近安装时间：{{ $installedAt }}</p>
            @endif

            <div class="form-actions install-locked-actions">
                <a class="button button-secondary" href="/">返回站点</a>
                <a class="button button-primary" href="/{{ $adminPath }}">进入管理后台</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>
