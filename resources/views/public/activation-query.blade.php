<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1020">
    <title>激活码查询</title>
    <link rel="stylesheet" href="/assets/css/activation-query.css?v=20261017-dark-tool">
</head>
<body>
@php
    $statusNames = [
        'inventory' => '未使用',
        'issued' => '已发出',
        'active' => '已激活',
        'expired' => '已到期',
        'consumed' => '已用完',
        'revoked' => '已删除',
    ];
@endphp
<main class="activation-query-card">
    <nav class="public-tools-nav" aria-label="公共工具">
        <a href="{{ route('activation.query') }}" aria-current="page">激活码查询</a>
        <a href="{{ route('tracking.unlock') }}">追踪解锁密码</a>
    </nav>

    <header>
        <p class="activation-query-kicker">PUBLIC TOOL · V1</p>
        <h1>激活码查询</h1>
        <p>支持激活码与充值卡状态查询，输入完整编码后点击查询即可。</p>
    </header>

    <section class="activation-query-body">
        <form method="post" action="{{ route('activation.query.run') }}">
            @csrf
            <label for="credential">激活码 / 充值卡</label>
            <input id="credential" name="credential" value="{{ old('credential', $credential) }}"
                   maxlength="255" autocomplete="off" placeholder="请输入激活码或充值卡编码" required autofocus>
            @error('credential')<p class="activation-query-error">{{ $message }}</p>@enderror
            <div class="activation-query-actions">
                <button type="submit">立即查询</button>
                <span>查询结果将显示在下方提示区域</span>
            </div>
        </form>

        @if($queried)
            @if($card)
                @php
                    if ($card->card_is_frozen) {
                        $resultClass = 'is-warning';
                        $resultBadge = '提示';
                        $resultMessage = '您查询的授权码已冻结';
                    } elseif ($card->status === 'revoked') {
                        $resultClass = 'is-danger';
                        $resultBadge = '提示';
                        $resultMessage = '您查询的授权码已删除';
                    } elseif ($card->status === 'expired' || ($card->expires_at && $card->expires_at->isPast())) {
                        $resultClass = 'is-danger';
                        $resultBadge = '提示';
                        $resultMessage = '您查询的授权码已到期';
                    } elseif ($card->activated_at) {
                        $resultClass = 'is-success';
                        $resultBadge = '正常';
                        $resultMessage = '您查询的授权码已激活';
                    } else {
                        $resultClass = 'is-warning';
                        $resultBadge = '提示';
                        $resultMessage = '您查询的授权码未激活';
                    }
                @endphp
                <section class="activation-query-result {{ $resultClass }}" aria-live="polite">
                    <div class="activation-query-result-summary">
                        <span>{{ $resultBadge }}</span>
                        <p>{{ $resultMessage }}</p>
                    </div>
                    @if($card->activated_at)
                    <dl class="activation-query-result-details">
                        <div><dt>所属软件</dt><dd>{{ $card->application->name }}</dd></div>
                        <div><dt>充值卡类型</dt><dd>{{ $card->cardType->name }}</dd></div>
                        <div><dt>卡片状态</dt><dd>{{ $statusNames[$card->status] ?? $card->status }}</dd></div>
                        <div><dt>冻结状态</dt><dd>{{ $card->card_is_frozen ? '已冻结' : '正常' }}</dd></div>
                        <div><dt>激活时间</dt><dd>{{ $card->activated_at?->format('Y-m-d H:i:s') ?? '尚未激活' }}</dd></div>
                        <div><dt>到期时间</dt><dd>{{ $card->expires_at?->format('Y-m-d H:i:s') ?? '按卡类型计算 / 永久' }}</dd></div>
                    </dl>
                    @endif
                </section>
            @else
                <section class="activation-query-result is-danger" aria-live="polite">
                    <div class="activation-query-result-summary">
                        <span>提示</span>
                        <p>您查询的授权码不存在，请核对完整编码</p>
                    </div>
                </section>
            @endif
        @endif
    </section>

    <footer>公共查询工具 · 请输入完整编码</footer>
</main>
</body>
</html>
