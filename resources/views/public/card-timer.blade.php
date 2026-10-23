<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1020">
    <title>计时卡冻结</title>
    <link rel="stylesheet" href="/assets/css/activation-query.css?v=20261024-card-timer">
</head>
<body>
@php
    $duration = function (int $seconds): string {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return ($days > 0 ? $days.' 天 ' : '').$hours.' 小时 '.$minutes.' 分钟';
    };
    $time = fn (string $iso): string => $iso === '' ? '—' : \Carbon\CarbonImmutable::parse($iso)->format('Y-m-d H:i:s');
@endphp
<main class="activation-query-card card-timer-page">

    <header>
        <p class="activation-query-kicker">用户自助</p>
        <h1>计时卡冻结</h1>
        <p>暂时不用时冻结计时，冻结期间不扣除时长；回来后点击恢复计时，或直接登录软件也会自动恢复。</p>
    </header>

    <section class="activation-query-body">
        <form method="post" action="{{ route('card.timer.run') }}">
            @csrf
            <input type="hidden" name="action" value="status">
            <label for="credential">计时卡卡号</label>
            <input id="credential" name="credential" value="{{ old('credential', $credential) }}"
                   maxlength="64" autocomplete="off" placeholder="请输入完整卡号" required autofocus>
            @error('credential')<p class="activation-query-error">{{ $message }}</p>@enderror
            <div class="activation-query-actions">
                <button type="submit">查询计时状态</button>
                <span>只有制卡时设为“计时卡”的卡可以冻结</span>
            </div>
        </form>

        @if($error)
            <section class="activation-query-result is-danger" aria-live="polite">
                <div class="activation-query-result-summary">
                    <span>提示</span>
                    <p>{{ $error }}</p>
                </div>
            </section>
        @elseif($timer)
            @php $paused = $timer['state'] === 'paused'; @endphp
            <section class="activation-query-result {{ $paused ? 'is-warning' : 'is-success' }}" aria-live="polite">
                <div class="activation-query-result-summary">
                    <span>{{ $paused ? '冻结中' : '计时中' }}</span>
                    <p>{{ $notice ?? ($paused ? '计时已冻结，当前不扣除时长' : '正在正常计时') }}</p>
                </div>
                <dl class="activation-query-result-details">
                    <div><dt>所属软件</dt><dd>{{ $timer['application_name'] }}</dd></div>
                    <div><dt>剩余时长</dt><dd>{{ $duration((int) $timer['remaining_seconds']) }}</dd></div>
                    <div><dt>到期时间</dt><dd>{{ $time($timer['expires_at']) }}{{ $paused ? '（按此刻恢复计算）' : '' }}</dd></div>
                    <div><dt>冻结次数</dt><dd>{{ $timer['pause_count'] }} / {{ $timer['pause_limit'] === -1 ? '不限' : $timer['pause_limit'] }}</dd></div>
                    @if($paused && $timer['auto_resume_at'] !== '')
                        <div><dt>自动恢复</dt><dd>{{ $time($timer['auto_resume_at']) }}</dd></div>
                    @endif
                </dl>
                <form method="post" action="{{ route('card.timer.run') }}" class="card-timer-action">
                    @csrf
                    <input type="hidden" name="credential" value="{{ $credential }}">
                    <input type="hidden" name="action" value="{{ $paused ? 'resume' : 'pause' }}">
                    <div class="activation-query-actions">
                        <button type="submit" class="{{ $paused ? '' : 'card-timer-pause' }}">{{ $paused ? '恢复计时' : '冻结计时' }}</button>
                        <span>{{ $paused ? '恢复后立即开始扣除时长，代理约 30 秒内恢复可用' : '冻结后约 30 秒内代理停止可用；再次登录软件会自动恢复计时' }}</span>
                    </div>
                </form>
            </section>
        @endif
    </section>

    <footer>计时卡自助服务 · 冻结与恢复</footer>
</main>
</body>
</html>
