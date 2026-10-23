<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1020">
    <title>计时卡冻结</title>
    <link rel="stylesheet" href="/assets/css/activation-query.css?v=20261024-card-timer-2">
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
        <p>不玩时冻结，冻结期间不扣时长。</p>
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
                <button type="submit">查询</button>
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
                    <p>{{ $notice ?? ($paused ? '已冻结，不扣时长' : '正在计时') }}</p>
                </div>
                <dl class="activation-query-result-details">
                    <div><dt>所属软件</dt><dd>{{ $timer['application_name'] }}</dd></div>
                    <div><dt>剩余时长</dt><dd>{{ $duration((int) $timer['remaining_seconds']) }}</dd></div>
                    @unless($paused)
                        <div><dt>到期时间</dt><dd>{{ $time($timer['expires_at']) }}</dd></div>
                    @endunless
                    @if($timer['pause_limit'] !== -1)
                        <div><dt>冻结次数</dt><dd>已用 {{ $timer['pause_count'] }} 次，共 {{ $timer['pause_limit'] }} 次</dd></div>
                    @endif
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
                        @unless($paused)
                            <p class="card-timer-warning">冻结后端口会拒绝连接，禁止在游玩时冻结卡密</p>
                        @endunless
                    </div>
                </form>
            </section>
        @endif
    </section>

    <footer>计时卡自助服务 · 冻结与恢复</footer>
</main>
</body>
</html>
