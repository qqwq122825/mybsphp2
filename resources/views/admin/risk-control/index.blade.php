@extends('admin.layout')

@section('title', ($selectedApplication?->name ?? '软件').' · 封控设置')

@section('content')
@if(!$selectedApplication)
    <section class="dashboard-panel risk-empty">
        <span>⌁</span><h2>还没有可配置的软件</h2><p>创建软件后即可自动生成独立封控策略。</p>
    </section>
@else
    @php
        $probeError = collect(['enabled', 'invalid_card_threshold', 'invalid_card_window_seconds', 'block_http_status', 'block_code', 'block_message'])
            ->contains(fn (string $field): bool => $errors->has($field));
        $honeypotError = collect(['security_report_mode', 'abnormal_exit_window_minutes', 'abnormal_exit_high_threshold'])
            ->contains(fn (string $field): bool => $errors->has($field));
        $defaultConfigTab = $honeypotError ? 'honeypot' : ($probeError ? 'probe' : (request('tab') === 'honeypot' ? 'honeypot' : 'probe'));
        $enabledValue = (string) old('enabled', $policy->enabled ? '1' : '0');
        $probeThreshold = max(2, (int) old('invalid_card_threshold', $policy->invalid_card_threshold ?? 3));
        $probeWindow = max(10, (int) old('invalid_card_window_seconds', $policy->invalid_card_window_seconds ?? 60));
        $probeWarns = $probeThreshold - 1;
    @endphp
    <div class="software-config-form" data-config-form data-default-config-tab="{{ $defaultConfigTab }}">
        <header class="software-settings-heading">
            <div>
                <span>RISK CONTROL SETTINGS</span>
                <h1>封控设置</h1>
                <p>软件代号 {{ $selectedApplication->code }}，配置套卡探测阈值与蜜罐处置策略；名单与手动拉黑请到封控中心。</p>
            </div>
            <a class="button button-secondary"
               href="{{ route('admin.risk-control.center', ['adminPath' => $adminPath]) }}"
               data-workspace-open data-workspace-title="封控中心">封控中心</a>
        </header>

        <nav class="software-config-tabs is-two" role="tablist" aria-label="封控设置分类">
            <button type="button" role="tab" data-config-tab="probe" aria-controls="risk-panel-probe">
                <span>01</span><strong>套卡探测</strong><small>错误次数与统计窗口</small>
            </button>
            <button type="button" role="tab" data-config-tab="honeypot" aria-controls="risk-panel-honeypot">
                <span>02</span><strong>蜜罐策略</strong><small>客户端安全事件与异常退出</small>
            </button>
        </nav>

        <div class="software-config-panel" id="risk-panel-probe" role="tabpanel" data-config-panel="probe">
            <form method="post" action="{{ route('admin.risk-control.policy.update', ['adminPath' => $adminPath, 'application' => $selectedApplication]) }}">
                @csrf
                @method('PUT')
                <section class="dashboard-panel form-panel form-panel-wide">
                    <div class="panel-body software-basic-rows">
                        <p class="risk-probe-rule-note">
                            <strong>当前规则</strong>
                            {{ $probeWindow }} 秒内只统计卡密不存在、空卡密、乱输入，正常登录不计入。
                            前 {{ $probeWarns }} 次只提示「卡密不存在」；第 {{ $probeThreshold }} 次还在尝试就拉黑当前设备和 IP。
                            拉黑后再登录仍显示卡密不存在。
                        </p>
                        <label class="software-basic-row">
                            <strong>套卡探测开关</strong>
                            <select name="enabled">
                                <option value="1" @selected($enabledValue === '1')>开启</option>
                                <option value="0" @selected($enabledValue === '0')>关闭</option>
                            </select>
                            <small>关闭后仍保留封控中心的人工拉黑与历史记录。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>统计窗口</strong>
                            <div class="honeypot-unit-input">
                                <input type="number" name="invalid_card_window_seconds" min="10" max="86400"
                                       value="{{ old('invalid_card_window_seconds', $policy->invalid_card_window_seconds ?? 60) }}">
                                <em>秒</em>
                            </div>
                            <small>只统计卡密不存在、空卡密、乱输入；正常登录不计入。例如 60 秒。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>错误次数阈值</strong>
                            <div class="honeypot-unit-input">
                                <input type="number" name="invalid_card_threshold" min="2" max="100"
                                       value="{{ old('invalid_card_threshold', $policy->invalid_card_threshold ?? 3) }}">
                                <em>次</em>
                            </div>
                            <small>填 3 表示前 2 次只提示卡密不存在，第 3 次再试就拉黑设备和 IP。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>当前运行模式</strong>
                            <input value="{{ $operationalMode === 'debug' ? '调试模式：开放封控明细' : '上线运行：谨慎响应' }}" readonly>
                            <small>上线运行固定返回卡密不存在；调试模式才使用下方状态码、错误码和提示。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>调试 HTTP 状态</strong>
                            <select name="block_http_status">
                                <option value="429" @selected((int) old('block_http_status', $policy->block_http_status) === 429)>429 · 请求过多</option>
                                <option value="403" @selected((int) old('block_http_status', $policy->block_http_status) === 403)>403 · 禁止访问</option>
                            </select>
                            <small>上线运行固定显示卡密不存在，不读取此项。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>调试业务错误码</strong>
                            <input name="block_code" value="{{ old('block_code', $policy->block_code) }}">
                            <small>上线响应固定为 INVALID_CARD。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>调试返回提示</strong>
                            <input name="block_message" value="{{ old('block_message', $policy->block_message) }}">
                            <small>上线运行固定返回「卡密不存在」。</small>
                        </label>
                    </div>
                </section>
                <div class="form-submit-bar">
                    <span>保存后立即用于下一次卡密登录。</span>
                    <button class="button button-primary" type="submit">确认保存</button>
                </div>
            </form>
        </div>

        <div class="software-config-panel" id="risk-panel-honeypot" role="tabpanel" data-config-panel="honeypot" hidden>
            <form method="post" action="{{ route('admin.risk-control.security-policy.update', ['adminPath' => $adminPath, 'application' => $selectedApplication]) }}">
                @csrf
                @method('PUT')
                <section class="dashboard-panel form-panel form-panel-wide">
                    <div class="panel-body software-basic-rows">
                        <label class="software-basic-row">
                            <strong>处置模式</strong>
                            <select name="security_report_mode">
                                <option value="disabled" @selected(old('security_report_mode', $policy->security_report_mode) === 'disabled')>关闭上报</option>
                                <option value="observe" @selected(old('security_report_mode', $policy->security_report_mode) === 'observe')>观察 · 只记录</option>
                                <option value="enforce" @selected(old('security_report_mode', $policy->security_report_mode) === 'enforce')>处置 · 高置信自动冻结</option>
                            </select>
                            <small>蜜罐事件与异常退出策略：控制 D0、D1、R0、S0 与 T1 的服务端处置；拉黑时同步处理设备和登录 IP。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>异常退出窗口</strong>
                            <div class="honeypot-unit-input">
                                <input type="number" name="abnormal_exit_window_minutes" min="15" max="60"
                                       value="{{ old('abnormal_exit_window_minutes', $policy->abnormal_exit_window_minutes) }}">
                                <em>分钟</em>
                            </div>
                            <small>可设置 15～60 分钟。高置信事件立即执行；普通异常退出达到阈值后冻结并拉黑。</small>
                        </label>
                        <label class="software-basic-row">
                            <strong>高级冻结阈值</strong>
                            <div class="honeypot-unit-input">
                                <input type="number" name="abnormal_exit_high_threshold" min="3" max="20"
                                       value="{{ old('abnormal_exit_high_threshold', $policy->abnormal_exit_high_threshold) }}">
                                <em>次</em>
                            </div>
                            <small>窗口内累计 3～20 次。设备名单与手动拉黑请到封控中心处理。</small>
                        </label>
                    </div>
                </section>
                <div class="form-submit-bar">
                    <span>保存后立即生效。</span>
                    <button class="button button-primary" type="submit">确认保存</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
