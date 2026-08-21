@extends('admin.layout')

@section('title', '系统信息设置')

@section('content')
<section class="dashboard-panel system-settings-panel">
    <header>
        <div><h2>系统信息设置</h2><small>站点显示、时区与客户端服务状态</small></div>
    </header>

    <form method="post" action="{{ route('admin.settings.system.update', ['adminPath' => $adminPath]) }}">
        @csrf @method('PUT')
        <div class="system-settings-rows">
            <label class="system-settings-row">
                <strong>系统名称</strong>
                <input name="app_name" value="{{ old('app_name', $settings['app_name']) }}" minlength="2" maxlength="80" required>
                <small>后台顶部、页面标题和系统信息中显示的名称</small>
            </label>

            <label class="system-settings-row">
                <strong>系统地址</strong>
                <input type="url" name="app_url" value="{{ old('app_url', $settings['app_url']) }}" maxlength="255" required placeholder="https://example.com">
                <small>填写对外访问地址，用于生成客户端接口和后台链接</small>
            </label>

            <label class="system-settings-row">
                <strong>后台入口</strong>
                <span class="system-admin-path-control">
                    <i aria-hidden="true">/</i>
                    <input name="admin_path" value="{{ old('admin_path', $settings['admin_path']) }}"
                           minlength="3" maxlength="48" pattern="[a-z0-9_-]+" required
                           autocomplete="off" spellcheck="false">
                </span>
                <small>支持 3～48 位小写字母、数字、短横线和下划线；保存后自动进入新的后台地址</small>
            </label>

            <label class="system-settings-row">
                <strong>代理入口</strong>
                <span class="system-admin-path-control">
                    <i aria-hidden="true">/</i>
                    <input name="agent_path" value="{{ old('agent_path', $settings['agent_path']) }}"
                           minlength="3" maxlength="48" pattern="[a-z0-9_-]+" required
                           autocomplete="off" spellcheck="false">
                    <i aria-hidden="true">/index.php</i>
                </span>
                <small>保存后代理登录页将使用新入口，旧的代理地址随即失效；代理入口不能与后台入口相同</small>
            </label>

            <label class="system-settings-row">
                <strong>时区设置</strong>
                <select name="app_timezone" required>
                    @foreach($timezones as $value => $label)
                        <option value="{{ $value }}" @selected(old('app_timezone', $settings['app_timezone']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <small>日志、卡片到期时间和后台统计统一使用该时区</small>
            </label>

            <div class="system-settings-row system-settings-readonly">
                <strong>服务器时间</strong>
                <span>{{ $serverTime }}</span>
                <small>时间直接取服务器时钟，避免额外时间差造成授权判断偏移</small>
            </div>

            <label class="system-settings-row system-runtime-mode-row">
                <strong>当前模式</strong>
                <select name="operational_mode" required>
                    <option value="production" @selected(old('operational_mode', $settings['operational_mode']) === 'production')>上线运行</option>
                    <option value="debug" @selected(old('operational_mode', $settings['operational_mode']) === 'debug')>调试模式</option>
                </select>
                <small>上线运行隐藏封控特征和内部故障；调试模式返回封控档位、等待时间及更详细的异常信息。</small>
            </label>

            <div class="system-settings-mode-note is-{{ old('operational_mode', $settings['operational_mode']) }}">
                <strong>{{ old('operational_mode', $settings['operational_mode']) === 'debug' ? '调试信息正在开放' : '当前按上线策略谨慎响应' }}</strong>
                <p>上线运行：低级封控保持正常成功结构，高级封控伪装为服务器故障。调试完成后建议切回上线运行。</p>
            </div>

            <label class="system-settings-row">
                <strong>客户端服务</strong>
                <select name="client_service_enabled" required>
                    <option value="1" @selected(old('client_service_enabled', $settings['client_service_enabled']) === '1')>正常开放</option>
                    <option value="0" @selected(old('client_service_enabled', $settings['client_service_enabled']) === '0')>暂停客户端接口</option>
                </select>
                <small>暂停后后台仍可管理，客户端登录、注册、激活及心跳接口返回维护提示</small>
            </label>

            <label class="system-settings-row system-settings-textarea-row">
                <strong>暂停提示</strong>
                <textarea name="client_service_message" rows="4" minlength="2" maxlength="500" required>{{ old('client_service_message', $settings['client_service_message']) }}</textarea>
                <small>客户端服务暂停时返回的提示内容</small>
            </label>

            <label class="system-settings-row system-runtime-mode-row">
                <strong>卡密登录请求日志</strong>
                <select name="card_login_trace_enabled" required>
                    <option value="0" @selected(old('card_login_trace_enabled', $settings['card_login_trace_enabled']) === '0')>关闭</option>
                    <option value="1" @selected(old('card_login_trace_enabled', $settings['card_login_trace_enabled']) === '1')>开启诊断记录</option>
                </select>
                <small>记录每次 login.ic 的解密请求、实际返回、请求设备和卡密绑定设备。卡密、密码、payload、session 与上报令牌会脱敏。</small>
            </label>

            <div class="system-settings-mode-note is-{{ old('card_login_trace_enabled', $settings['card_login_trace_enabled']) === '1' ? 'debug' : 'production' }}">
                <strong>{{ old('card_login_trace_enabled', $settings['card_login_trace_enabled']) === '1' ? '卡密登录诊断正在记录' : '卡密登录诊断已关闭' }}</strong>
                <p>排查“设备校验失败”时可临时开启，到“系统日志 → 客户端请求日志”对比请求设备、绑定设备和返回设备。</p>
            </div>

            <label class="system-settings-row system-runtime-mode-row">
                <strong>是否启用万能卡密</strong>
                <select name="universal_card_enabled" required>
                    <option value="0" @selected(old('universal_card_enabled', $settings['universal_card_enabled']) === '0')>关闭（所有 App 停用）</option>
                    <option value="1" @selected(old('universal_card_enabled', $settings['universal_card_enabled']) === '1')>开启（所有 App 启用）</option>
                </select>
                <small>这是全局开关。关闭后所有 App 都不接受万能卡密；开启后所有卡密登录型 App 立即生效。每次使用都会记录日志。</small>
            </label>

            <div class="system-settings-row system-runtime-mode-row">
                <strong>万能卡密</strong>
                <span class="system-universal-card-control">
                    <input type="text" name="universal_card_code"
                           form="universal-card-update-form" minlength="8" maxlength="64" required
                           autocomplete="off" spellcheck="false"
                           value="{{ old('universal_card_code', $settings['universal_card_code']) }}"
                           placeholder="输入新的万能卡密">
                    <button class="button button-primary" type="submit" form="universal-card-update-form">确定</button>
                </span>
                <small>
                    @if($settings['universal_card_code'] !== '')
                        当前万能卡密会直接显示。
                    @else
                        该值由历史版本保存为摘要，请重新输入一次后即可回显。
                    @endif
                    确定后新卡密会立即替换旧卡密，数据库使用 APP_KEY 加密保存可回显值。
                </small>
            </div>

            <div class="system-settings-security-note">
                <strong>安全配置</strong>
                <p>系统加密密钥与 Cookie 密钥继续由服务器环境配置管理，页面仅维护日常运营参数。</p>
            </div>

            @can('settings.appearance.update')
                <div class="system-settings-actions"><button class="button button-primary" type="submit">确认保存</button></div>
            @endcan
        </div>
    </form>
    <form id="universal-card-update-form" method="post" action="{{ route('admin.settings.system.universal-card.update', ['adminPath' => $adminPath]) }}">
        @csrf @method('PUT')
    </form>
</section>
@endsection
