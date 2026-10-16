<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1020">
    <title>代理 · 追踪解锁密码</title>
    <link rel="stylesheet" href="/assets/css/tracking-unlock.css?v=20261016-public-tool">
    <script src="/assets/js/tracking-unlock.js?v=20261016-public-tool" defer></script>
</head>
<body>
<main class="tracking-unlock-card">
    <nav class="public-tools-nav" aria-label="公共工具">
        <a href="{{ route('activation.query') }}">激活码查询</a>
        <a href="{{ route('tracking.unlock') }}" aria-current="page">追踪解锁密码</a>
    </nav>

    <header>
        <p class="tracking-unlock-kicker">PUBLIC TOOL · V1</p>
        <h1>代理 · 追踪解锁密码</h1>
        <p>输入用户卡密，点击显示，将生成的 8 位密码发给用户。</p>
    </header>

    <section class="tracking-unlock-body">
        <label for="card">卡密（区分大小写）</label>
        <input id="card" autocomplete="off" spellcheck="false" placeholder="请输入卡密" autofocus>
        <button id="show" type="button">显示解锁密码</button>
        <output id="code" aria-live="polite" aria-label="解锁密码"></output>
        <p id="error" role="alert"></p>
        <button id="copy" type="button" class="is-secondary">复制密码</button>
    </section>

    <footer>算法版本 v1 · 此工具仅交付代理使用</footer>
</main>
</body>
</html>
