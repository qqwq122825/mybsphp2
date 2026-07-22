@extends('admin.layout')

@php
    $editing = $application !== null;
@endphp
@section('title', $editing ? '软件配置' : '添加新软件')

@section('content')
    @if (! $editing)
        <header class="page-heading page-heading-actions">
            <div>
                <p class="eyebrow">NEW SOFTWARE</p>
                <h1>添加新软件</h1>
                <p>先建立软件，其他参数在创建完成后进入配置页设置。</p>
            </div>
            <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回列表</a>
        </header>
    @endif

    @if (! $editing)
        <form class="admin-form-grid software-create-grid" method="post"
              action="{{ route('admin.applications.store', ['adminPath' => $adminPath]) }}">
            @csrf

            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>CREATE SOFTWARE</span><h2>软件基本信息</h2></div><em>三项必填</em></header>
                <div class="panel-body software-create-body">
                    <div class="software-create-fields stack-form">
                        <label class="field">
                            <span>软件模式</span>
                            <select name="mode" required>
                                <option value="card_login" @selected(old('mode', 'card_login') === 'card_login')>卡串验证限时模式</option>
                                <option value="account_recharge" @selected(old('mode') === 'account_recharge')>账号登录限时模式</option>
                            </select>
                            <small>选择客户端使用卡密直接登录，或使用账户注册后充值。</small>
                        </label>
                        <label class="field">
                            <span>软件名称</span>
                            <input name="name" value="{{ old('name') }}" maxlength="120"
                                   placeholder="软件显示名称" required>
                            <small>支持中文、字母和数字。</small>
                        </label>
                        <label class="field">
                            <span>软件代号</span>
                            <input name="code" value="{{ old('code') }}" maxlength="20"
                                   inputmode="numeric" pattern="[0-9]+"
                                   placeholder="例如 201 或 12345678" required>
                            <small>只允许数字，创建后保持不变。</small>
                        </label>
                    </div>

                    <aside class="software-mode-guide">
                        <span>模式说明</span>
                        <article>
                            <b>卡串验证限时模式</b>
                            <p>后台生成卡密并手动交付，客户端直接使用卡密登录；有效期由卡种决定。</p>
                        </article>
                        <article>
                            <b>账号登录限时模式</b>
                            <p>用户注册账户后登录，再使用充值卡增加时长或点数；账户业务将在后续任务继续完善。</p>
                        </article>
                    </aside>
                </div>
            </section>

            <div class="form-submit-bar">
                <span>创建后会自动进入软件配置页。</span>
                <button class="button button-primary" type="submit">确认添加</button>
            </div>
        </form>
    @else
        @php
            $communicationError = collect(['encryption_mode', 'max_clock_skew_seconds'])
                ->contains(fn (string $field): bool => $errors->has($field));
            $limitError = collect(['bind_device_enabled', 'max_bound_devices', 'max_concurrent_sessions', 'heartbeat_interval_seconds', 'connection_timeout_seconds', 'rebind_deduction_value', 'rebind_deduction_unit'])
                ->contains(fn (string $field): bool => $errors->has($field));
            $defaultConfigTab = $communicationError ? 'communication' : ($limitError ? 'limits' : 'basic');
        @endphp
        <form class="software-config-form" method="post" data-config-form
              data-default-config-tab="{{ $defaultConfigTab }}"
              action="{{ route('admin.applications.update', ['adminPath' => $adminPath, 'application' => $application]) }}">
            @csrf
            @method('PUT')

            <header class="software-settings-heading">
                <div>
                    <span>SOFTWARE SETTINGS</span>
                    <h1>软件配置</h1>
                    <p>软件代号 {{ $application->code }}，验证数据将作为客户端返回数组的 a[2]。</p>
                </div>
                <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回列表</a>
            </header>

            <nav class="software-config-tabs" role="tablist" aria-label="软件配置分类">
                <button type="button" role="tab" data-config-tab="basic" aria-controls="software-panel-basic">
                    <span>01</span><strong>基本配置</strong><small>名称、状态和验证数据</small>
                </button>
                <button type="button" role="tab" data-config-tab="communication" aria-controls="software-panel-communication">
                    <span>02</span><strong>通信安全</strong><small>地址、密钥和接口参数</small>
                </button>
                <button type="button" role="tab" data-config-tab="limits" aria-controls="software-panel-limits">
                    <span>03</span><strong>限开控制</strong><small>设备绑定和并发限制</small>
                </button>
            </nav>

            <div class="admin-form-grid software-config-panel" id="software-panel-communication"
                 role="tabpanel" data-config-panel="communication" hidden>
            <section class="dashboard-panel form-panel form-panel-wide communication-panel">
                <header><div><span>CONNECTION SECURITY</span><h2>通信安全</h2></div><em>创建时自动生成</em></header>
                <div class="panel-body stack-form">
                    <div class="connection-row">
                        <div><span>软件代号</span><strong>{{ $application->code }}</strong></div>
                        <button type="button" data-copy="{{ $application->code }}">复制</button>
                    </div>
                    <div class="connection-row">
                        <div><span>通信认证 Key</span><code>{{ $application->communicationKey() }}</code></div>
                        <button type="button" data-copy="{{ $application->communicationKey() }}">复制 Key</button>
                    </div>
                    <div class="connection-row connection-row-address">
                        <div><span>服务器地址</span><code>{{ $serverAddress }}</code></div>
                        <button type="button" data-copy="{{ $serverAddress }}">复制地址</button>
                    </div>

                    <div class="field encryption-mode-field">
                        <span>数据包加密</span>
                        <input type="hidden" name="encryption_mode" value="hybrid_rsa_aes_hmac">
                        <strong class="encryption-mode-value">现代混合加密（RSA-OAEP + AES-256-CBC + HMAC）</strong>
                        <small>新客户端使用服务器公钥封装临时会话密钥，再用 AES-CBC + HMAC 加密并校验请求和响应；可直接使用 iOS Security.framework 与 CommonCrypto。</small>
                    </div>

                    <details class="certificate-panel">
                        <summary><span><strong>证书与客户端配置</strong><small>服务器公钥、Objective-C 配置宏</small></span><i>展开</i></summary>
                        <div class="certificate-grid">
                            <label class="field">
                                <span>服务器公钥 <small>复制到客户端</small></span>
                                <textarea rows="6" readonly>{{ $application->server_public_key }}</textarea>
                                <button class="inline-copy-button" type="button" data-copy="{{ $application->server_public_key }}">复制服务器公钥</button>
                            </label>
                            <label class="field">
                                <span>Objective-C 配置宏</span>
                                <textarea rows="6" readonly>{{ $objectiveCMacros }}</textarea>
                                <button class="inline-copy-button" type="button" data-copy="{{ $objectiveCMacros }}">复制客户端配置</button>
                            </label>
                        </div>
                    </details>

                </div>
            </section>
            </div>

            <div class="admin-form-grid software-config-panel" id="software-panel-basic"
                 role="tabpanel" data-config-panel="basic">
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>BASIC SETTINGS</span><h2>基本配置</h2></div><em>{{ $application->code }}</em></header>
                <div class="panel-body software-basic-rows">
                    <label class="software-basic-row">
                        <strong>软件代号</strong><input value="{{ $application->code }}" readonly><small>软件唯一代号，创建后保持不变</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>软件名称</strong><input name="name" value="{{ old('name', $application->name) }}" maxlength="120" required><small>后台和客户端显示的软件名称</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>软件开关</strong>
                        <select name="status">
                            @foreach (['active' => '正常开放', 'draft' => '草稿', 'suspended' => '暂停使用', 'archived' => '已归档'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $application->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small>只有“正常开放”允许客户端登录和激活；其他状态均返回关闭提示</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>关闭提示</strong>
                        <input name="closed_message" value="{{ old('closed_message', $application->closed_message) }}" maxlength="500"
                               placeholder="系统维护，暂时关闭，请稍后再访问！">
                        <small>软件关闭时返回给客户端展示的文字</small>
                    </label>
                    <label class="software-basic-row software-basic-row-textarea">
                        <strong>软件说明</strong>
                        <textarea name="description" rows="3" maxlength="500" placeholder="内部备注或软件介绍">{{ old('description', $application->description) }}</textarea>
                        <small>软件内部备注或介绍，仅后台管理使用</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>版本号</strong><input name="version_name" value="{{ old('version_name', $application->version_name) }}" maxlength="60" placeholder="例如 v1.0"><small>客户端可读取的当前版本</small>
                    </label>
                    <label class="software-basic-row">
                        <strong>软件公告</strong><input name="announcement" value="{{ old('announcement', $application->announcement) }}" maxlength="5000" placeholder="客户端需要展示的公告"><small>客户端启动后需要展示的公告内容</small>
                    </label>
                    @if($application->mode === 'account_recharge')
                            <label class="software-basic-row">
                                <strong>首次注册赠送</strong>
                                <div class="inline-unit-input software-invite-time">
                                <input type="number" name="registration_bonus_value" min="0" max="3650"
                                       value="{{ old('registration_bonus_value', $application->registration_bonus_value ?? 0) }}" required>
                                <select name="registration_bonus_unit" required>
                                    @foreach(['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('registration_bonus_unit', $application->registration_bonus_unit ?? 'hour') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                </div>
                                <small>仅账号登录限时模式生效；新用户注册成功后赠送，0 表示关闭</small>
                            </label>
                            <label class="software-basic-row">
                                <strong>邀请赠送时长</strong>
                                <div class="inline-unit-input software-invite-time">
                                <input type="number" name="invitation_bonus_value" min="0" max="3650"
                                       value="{{ old('invitation_bonus_value', $application->invitation_bonus_value ?? 0) }}" required>
                                <select name="invitation_bonus_unit" required>
                                    @foreach(['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('invitation_bonus_unit', $application->invitation_bonus_unit ?? 'day') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                </div>
                                <small>邀请人与新用户双方增加这段时间，0 表示关闭</small>
                            </label>
                    @else
                        <input type="hidden" name="registration_bonus_value" value="{{ $application->registration_bonus_value ?? 0 }}">
                        <input type="hidden" name="registration_bonus_unit" value="{{ $application->registration_bonus_unit ?? 'hour' }}">
                        <input type="hidden" name="invitation_bonus_value" value="{{ $application->invitation_bonus_value ?? 0 }}">
                        <input type="hidden" name="invitation_bonus_unit" value="{{ $application->invitation_bonus_unit ?? 'day' }}">
                    @endif
                </div>
            </section>

            <section class="dashboard-panel form-panel form-panel-wide verification-panel">
                <header><div><span>VERIFICATION DATA</span><h2>验证数据</h2></div><em>a[2]</em></header>
                <div class="panel-body software-basic-rows verification-basic-rows">
                    <label class="software-basic-row software-basic-row-textarea verification-data-field">
                        <strong>验证数据</strong>
                        <textarea name="verification_data" rows="9" maxlength="262144"
                                  placeholder="例如 0x100000，也可以填写地址、文本、JSON 或其他业务数据；不需要时留空。">{{ old('verification_data', $application->verificationData()) }}</textarea>
                        <small>登录或激活成功时原样写入客户端返回数组 a[2]</small>
                    </label>
                </div>
            </section>
            </div>

            <div class="admin-form-grid software-config-panel" id="software-panel-limits"
                 role="tabpanel" data-config-panel="limits" hidden>
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>DEVICE & SESSION LIMITS</span><h2>限开控制</h2></div><em>设备与并发</em></header>
                <div class="panel-body stack-form">
                    <label class="field">
                        <span>是否绑定模式</span>
                        <select name="bind_device_enabled" required>
                            <option value="1" @selected((string) old('bind_device_enabled', (int) $application->bind_device_enabled) === '1')>验证绑定特征</option>
                            <option value="0" @selected((string) old('bind_device_enabled', (int) $application->bind_device_enabled) === '0')>免验证绑定特征</option>
                        </select>
                        <small>验证模式会校验设备特征；免验证时不限制绑定设备，同一账号可按最大并发会话数多设备登录。</small>
                    </label>
                    <label class="field">
                        <span>转绑定扣除时长</span>
                        <div class="inline-unit-input software-invite-time">
                            <input type="number" name="rebind_deduction_value" min="0" max="3650"
                                   value="{{ old('rebind_deduction_value', $application->rebind_deduction_value ?? 0) }}" required>
                            <select name="rebind_deduction_unit" required>
                                @foreach(['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('rebind_deduction_unit', $application->rebind_deduction_unit ?? 'hour') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small>用户更换已绑定设备时，从剩余授权时间中扣除；0 表示不扣除。</small>
                    </label>
                    <div class="field-grid">
                        <label class="field">
                            <span>最多绑定设备</span>
                            <input type="number" name="max_bound_devices" min="1" max="20"
                                   value="{{ old('max_bound_devices', $application->max_bound_devices) }}" required>
                        </label>
                        <label class="field">
                            <span>最大并发会话</span>
                            <input type="number" name="max_concurrent_sessions" min="1" max="100"
                                   value="{{ old('max_concurrent_sessions', $application->max_concurrent_sessions) }}" required>
                        </label>
                        <label class="field">
                            <span>心跳间隔（秒）</span>
                            <input type="number" name="heartbeat_interval_seconds" min="5" max="3600"
                                   value="{{ old('heartbeat_interval_seconds', $application->heartbeat_interval_seconds ?? 30) }}" required>
                        </label>
                        <label class="field">
                            <span>连接超时（秒）</span>
                            <input type="number" name="connection_timeout_seconds" min="15" max="86400"
                                   value="{{ old('connection_timeout_seconds', $application->connection_timeout_seconds ?? 120) }}" required>
                            <small>客户端需在超时前调用 timeout.ic 或 timeout.lg。</small>
                        </label>
                    </div>
                </div>
            </section>
            </div>

            <div class="admin-form-grid software-config-panel" role="tabpanel"
                 data-config-panel="communication" hidden>
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>API SETTINGS</span><h2>接口参数</h2></div><em>自动匹配软件模式</em></header>
                <div class="panel-body stack-form">
                    @if($application->mode === 'account_recharge')
                        <div class="connection-row software-api-list">
                            <div>
                                <span>账号模式 API</span>
                                <strong>GetPleaseregister.lg · registration.lg · registrationaska.lg · login.lg · chong.lg · timeout.lg</strong>
                            </div>
                            <small>查账号、普通注册、带卡注册、登录、充值续期；统一走当前加密通道。</small>
                        </div>
                    @endif
                    <label class="field">
                        <span>时间容差（秒）</span>
                        <input type="number" name="max_clock_skew_seconds" min="30" max="3600"
                               value="{{ old('max_clock_skew_seconds', $application->legacyConfig?->max_clock_skew_seconds ?? 300) }}">
                    </label>
                </div>
            </section>
            </div>

            <div class="form-submit-bar">
                <span>保存后立即应用当前软件的全部配置。</span>
                <button class="button button-primary" type="submit">确认保存</button>
            </div>
        </form>
    @endif
@endsection
