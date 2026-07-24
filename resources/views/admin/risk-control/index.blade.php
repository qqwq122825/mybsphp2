@extends('admin.layout')

@section('title', 'IP 封控中心')

@section('content')
<header class="page-heading page-heading-actions risk-page-heading">
    <div>
        <p class="eyebrow">IP RISK CONTROL</p>
        <h1>IP 封控中心</h1>
        <p>按短时登录频率自动分档；上线时隐藏封控特征，调试时显示判定明细。</p>
    </div>
    @if($applications->isNotEmpty())
        <div class="risk-heading-tools">
            <form class="risk-app-switcher" method="get">
                <label for="risk-application">当前软件策略</label>
                <select id="risk-application" name="application" onchange="this.form.submit()">
                    @foreach($applications as $application)
                        <option value="{{ $application->public_id }}" @selected($selectedApplication?->is($application))>
                            {{ $application->name }} · {{ $application->code }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    @endif
</header>

@if(!$selectedApplication)
    <section class="dashboard-panel risk-empty">
        <span>⌁</span><h2>还没有可配置的软件</h2><p>创建软件后即可自动生成独立封控策略。</p>
    </section>
@else
    <section class="risk-status-strip {{ $policy->enabled ? 'is-on' : 'is-off' }}">
        <div><span class="risk-pulse"></span><strong>{{ $policy->enabled ? '自动封控运行中' : '自动封控已暂停' }}</strong></div>
        <p>{{ $selectedApplication->name }} · 最近 {{ $policy->window_seconds }} 秒滚动窗口 · 每个 IP 独立计数</p>
        <span>{{ $selectedApplication->mode === 'card_login' ? '卡串验证模式' : '账号登录模式' }}</span>
    </section>

    <section class="risk-response-mode is-{{ $operationalMode }}">
        <div><span>{{ $operationalMode === 'debug' ? 'DEBUG' : 'LIVE' }}</span><strong>{{ $operationalMode === 'debug' ? '调试模式：开放封控明细' : '上线运行：谨慎响应' }}</strong></div>
        @if($operationalMode === 'debug')
            <p>低级成功响应附带 <code>risk_control</code>；高级按下方状态码、错误码和提示返回，并携带等待时间。</p>
        @else
            <p>低级保持 HTTP 200、正常到期时间和原字段布局，仅替换验证值；高级统一返回 503 服务器故障，不暴露封控等级、阈值和封禁时长。</p>
        @endif
        <a href="{{ route('admin.settings.system.index', ['adminPath' => $adminPath]) }}">切换当前模式</a>
    </section>

    <section class="metric-grid risk-metric-grid">
        <article class="metric-card metric-blue"><span>24 小时命中</span><strong>{{ number_format($metrics['events_24h']) }}</strong><small>低级与高级事件总数</small></article>
        <article class="metric-card metric-orange"><span>低级观察</span><strong>{{ number_format($metrics['low']) }}</strong><small>验证数据替换 / 仅记录</small></article>
        <article class="metric-card metric-red"><span>高级风险</span><strong>{{ number_format($metrics['high']) }}</strong><small>已达到阻断档位</small></article>
        <article class="metric-card metric-green"><span>正在封禁</span><strong>{{ number_format($metrics['blocked']) }}</strong><small>临时或长期封禁</small></article>
    </section>

    <div class="risk-layout">
        <section class="dashboard-panel risk-policy-panel">
            <header>
                <div><p class="eyebrow">POLICY</p><h2>自动分档策略</h2><small>参数保存后立即用于下一次登录请求</small></div>
                <span class="risk-policy-badge">{{ $policy->enabled ? '已启用' : '已停用' }}</span>
            </header>

            <form method="post" action="{{ route('admin.risk-control.policy.update', ['adminPath' => $adminPath, 'application' => $selectedApplication]) }}">
                @csrf
                @method('PUT')
                <div class="risk-enable-row">
                    <div><strong>启用自动封控</strong><small>关闭后仍保留人工封禁状态与历史记录</small></div>
                    <label class="risk-switch"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $policy->enabled))><span></span></label>
                </div>

                <div class="risk-window-row">
                    <label><span>滚动统计窗口</span><div><input type="number" name="window_seconds" min="10" max="3600" value="{{ old('window_seconds', $policy->window_seconds) }}"><em>秒</em></div></label>
                    <label><span>高级封禁时长</span><div><input type="number" name="block_duration_minutes" min="1" max="43200" value="{{ old('block_duration_minutes', $policy->block_duration_minutes) }}"><em>分钟</em></div></label>
                </div>

                <section class="risk-window-explainer">
                    <header><div><strong>滚动窗口如何计数</strong><small>以 10 秒窗口为例：每次请求都向前回看，包含本次请求</small></div><span>不是按整段时间清零</span></header>
                    <div class="risk-timeline" aria-label="10 秒滚动窗口示例">
                        <div><i class="is-normal"></i><strong>0 秒</strong><span>请求 1</span></div>
                        <div><i class="is-normal"></i><strong>3 秒</strong><span>请求 2</span></div>
                        <div><i class="is-low"></i><strong>6 秒</strong><span>请求 3 · 触发低级</span></div>
                        <div><i class="is-low"></i><strong>11 秒</strong><span>请求 4 · 仍计 3 次</span></div>
                    </div>
                    <p>第 4 次发生在第 11 秒时，第 1 次已经移出窗口；但第 3、6、11 秒三次仍处于最近 10 秒内，所以计数仍为 3。恰好 10 秒前的请求不计入。</p>
                </section>

                <div class="risk-level-stack">
                    <article class="risk-level-card risk-level-normal">
                        <b>01</b><div><strong>正常放行</strong><p>低于低级阈值，返回真实业务数据。</p></div><span>&lt; {{ $policy->low_threshold }} 次</span>
                    </article>
                    <article class="risk-level-card risk-level-low">
                        <b>02</b><div><strong>低级封控</strong><p>仍按成功响应返回原到期时间，不出现频率提示；可静默替换验证数据。</p></div>
                        <label><input type="number" name="low_threshold" min="2" value="{{ old('low_threshold', $policy->low_threshold) }}"><span>次触发</span></label>
                    </article>
                    <article class="risk-level-card risk-level-high">
                        <b>03</b><div><strong>高级封控</strong><p>上线运行伪装为 503 服务器故障；调试模式返回封控明细。</p></div>
                        <label><input type="number" name="high_threshold" min="3" value="{{ old('high_threshold', $policy->high_threshold) }}"><span>次触发</span></label>
                    </article>
                </div>

                <div class="risk-policy-fields">
                    <label><span>低级处理方式</span><select name="low_action"><option value="decoy" @selected(old('low_action',$policy->low_action)==='decoy')>静默替换验证数据</option><option value="observe" @selected(old('low_action',$policy->low_action)==='observe')>仅记录观察</option></select><small>没有验证数据的软件会保持原成功结果，仅记录风险。</small></label>
                    <label><span>替换值</span><input name="decoy_value" value="{{ old('decoy_value',$policy->decoy_value) }}" placeholder="留空则每次随机生成"><small>建议留空，降低固定特征。</small></label>
                    <label><span>调试模式 HTTP 状态</span><select name="block_http_status"><option value="429" @selected((int)old('block_http_status',$policy->block_http_status)===429)>429 · 请求过多</option><option value="403" @selected((int)old('block_http_status',$policy->block_http_status)===403)>403 · 禁止访问</option></select><small>上线运行固定使用 503，不读取此项。</small></label>
                    <label><span>调试模式业务错误码</span><input name="block_code" value="{{ old('block_code',$policy->block_code) }}"><small>上线响应固定为 SERVER_UNAVAILABLE。</small></label>
                    <label class="risk-field-wide"><span>调试模式返回提示</span><input name="block_message" value="{{ old('block_message',$policy->block_message) }}"><small>上线运行固定返回“服务器暂时故障，请稍后再试”。</small></label>
                </div>
                <footer><p>推荐基线：最近 <strong>10 秒</strong>内 <strong>3 次低级</strong>、<strong>5 次高级</strong>，高级封禁 30 分钟。</p><button class="button button-primary" type="submit">保存并启用策略</button></footer>
            </form>
        </section>

        <aside class="dashboard-panel risk-feed-panel">
            <header><div><p class="eyebrow">LIVE FEED</p><h2>最近命中</h2></div><span>最新 12 条</span></header>
            <div class="risk-feed-list">
                @forelse($recentEvents as $event)
                    <article><span class="risk-feed-dot is-{{ $event->level }}"></span><div><strong>{{ $event->ip }}</strong><p>{{ $event->reason }}</p><small>{{ $event->created_at?->diffForHumans() }} · {{ $event->window_attempts }} 次</small></div><em>{{ $event->level === 'high' ? '高级' : '低级' }}</em></article>
                @empty
                    <div class="risk-feed-empty"><span>✓</span><strong>暂未命中风险</strong><p>新事件会实时沉淀在这里。</p></div>
                @endforelse
            </div>
        </aside>
    </div>

    <x-admin.table-shell title="风险 IP 列表" subtitle="只显示进入低级或高级档位的 IP" panel-class="risk-table-panel">
        <x-slot:search>
            <form class="risk-filter" method="get">
                <input type="hidden" name="application" value="{{ $selectedApplication->public_id }}">
                <label><span>风险等级</span><select name="level"><option value="all">全部等级</option><option value="low" @selected(request('level')==='low')>低级封控</option><option value="high" @selected(request('level')==='high')>高级封控</option></select></label>
                <label class="risk-filter-keyword"><span>IP 地址</span><input name="keyword" value="{{ request('keyword') }}" placeholder="输入完整或部分 IP"></label>
                <button class="button button-primary" type="submit">筛选</button>
                <a href="{{ route('admin.risk-control.index', ['adminPath'=>$adminPath,'application'=>$selectedApplication->public_id]) }}">重置</a>
            </form>
        </x-slot:search>
        <table>
            <thead><tr><th>风险等级</th><th>IP 地址</th><th>窗口请求</th><th>累计请求</th><th>最近接口</th><th>最后出现</th><th>封禁状态</th><th class="risk-actions-column">处置</th></tr></thead>
            <tbody>
            @forelse($states as $state)
                <tr>
                    <td><span class="risk-level-pill is-{{ $state->level }}"><i></i>{{ $state->level === 'high' ? '高级' : '低级' }}</span></td>
                    <td><strong class="risk-ip">{{ $state->ip }}</strong>@if($state->note)<small class="risk-note">{{ $state->note }}</small>@endif</td>
                    <td><strong>{{ $state->window_attempts }}</strong> 次</td><td>{{ number_format($state->total_attempts) }}</td>
                    <td><code>{{ $state->last_endpoint ?: '—' }}</code></td><td>{{ $state->last_seen_at?->format('m-d H:i:s') ?: '—' }}</td>
                    <td>
                        @if($state->permanently_blocked)<span class="risk-block-status is-blocked">长期封禁</span>
                        @elseif($state->blocked_until?->isFuture())<span class="risk-block-status is-blocked">至 {{ $state->blocked_until->format('m-d H:i') }}</span>
                        @else<span class="risk-block-status">观察中</span>@endif
                    </td>
                    <td>
                        <details class="risk-action-menu"><summary>处置 <span>⌄</span></summary><div>
                            @foreach(['reset'=>'解除并清零','set_low'=>'调整为低级','block_1h'=>'封禁 1 小时','block_24h'=>'封禁 24 小时','block_permanent'=>'长期封禁'] as $action => $label)
                                <form method="post" action="{{ route('admin.risk-control.states.action', ['adminPath'=>$adminPath,'state'=>$state]) }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><button type="submit">{{ $label }}</button></form>
                            @endforeach
                        </div></details>
                    </td>
                </tr>
            @empty
                <tr><td class="empty-cell" colspan="8">当前筛选范围内没有风险 IP。</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($states->hasPages())
            <x-slot:footer>
                <div class="pager">{{ $states->links() }}</div>
            </x-slot:footer>
        @endif
    </x-admin.table-shell>
@endif
@endsection
