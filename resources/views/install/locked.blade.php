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
        <div class="install-content install-locked-content">
            <span class="installed-check" aria-hidden="true">✓</span>
            <h1 id="installed-title">已安装</h1>
            <p>如果需要重新安装，请删除 <strong>installed.lock</strong> 文件。</p>
        </div>
    </section>
</main>
</body>
</html>
