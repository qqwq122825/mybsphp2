<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isProduction ? '我的生活记录' : $appName }}</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css?v=20260725-site-home">
</head>
<body class="site-page {{ $isProduction ? 'site-page-diary' : 'site-page-status' }}">
@if($isProduction)
    <main class="diary-card">
        <header class="diary-header">
            <div class="diary-avatar">M</div>
            <div>
                <p class="diary-kicker">MY LITTLE JOURNAL</p>
                <h1>我的生活记录</h1>
                <p class="diary-subtitle">记录日常、灵感和正在发生的小事。</p>
            </div>
        </header>

        <section class="diary-note diary-note-featured">
            <div class="diary-note-meta"><span>今日随笔</span><time datetime="{{ now()->toDateString() }}">{{ now()->format('Y年m月d日') }}</time></div>
            <h2>把平凡的日子过得认真一点</h2>
            <p>天气不错，给自己留一点安静的时间。整理房间、读几页书，也别忘了记录那些让人心情变好的瞬间。</p>
        </section>

        <section class="diary-grid" aria-label="最近记录">
            <article class="diary-note">
                <div class="diary-note-meta"><span>生活</span><time datetime="2026-07-24">07月24日</time></div>
                <h2>周末的慢节奏</h2>
                <p>一杯咖啡，一段音乐，下午的阳光刚刚好。</p>
            </article>
            <article class="diary-note">
                <div class="diary-note-meta"><span>想法</span><time datetime="2026-07-21">07月21日</time></div>
                <h2>保持好奇</h2>
                <p>小小的尝试也值得被记住，慢慢积累就会有新的收获。</p>
            </article>
        </section>

        <footer class="diary-footer">愿每天都有值得收藏的一页。</footer>
    </main>
@else
    <main class="site-card">
        <span class="brand-mark">BS</span>
        <p class="eyebrow">MYBSPHP2</p>
        <h1>{{ $appName }}</h1>
        <p>授权与发卡管理服务正在运行。</p>
        <span class="system-health is-online"><i></i>系统运行正常</span>
    </main>
@endif
</body>
</html>
