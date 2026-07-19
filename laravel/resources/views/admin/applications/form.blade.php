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
                                <option value="card_login" @selected(old('mode', 'card_login') === 'card_login')>卡密验证限时模式</option>
                                <option value="account_recharge" @selected(old('mode') === 'account_recharge')>账户注册与充值模式</option>
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
                            <b>卡密验证限时模式</b>
                            <p>后台生成卡密并手动交付，客户端直接使用卡密登录；有效期由卡种决定。</p>
                        </article>
                        <article>
                            <b>账户注册与充值模式</b>
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
            $loginApiName = $application->defaultLoginApiName();
            $communicationError = collect(['encryption_mode', 'max_clock_skew_seconds', 'legacy_enabled'])
                ->contains(fn (string $field): bool => $errors->has($field));
            $limitError = collect(['bind_device_enabled', 'max_bound_devices', 'max_concurrent_sessions'])
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

                    <details class="legacy-key-panel">
                        <summary>查看旧版数据包兼容密钥</summary>
                        <div>
                            <code>{{ $application->legacyPacketKey() }}</code>
                            <button type="button" data-copy="{{ $application->legacyPacketKey() }}">复制兼容密钥</button>
                        </div>
                        <p>仅供旧版 3DES 数据包使用；现代模式不把服务器私钥放进客户端。</p>
                    </details>
                </div>
            </section>
            </div>

            <div class="admin-form-grid software-config-panel" id="software-panel-basic"
                 role="tabpanel" data-config-panel="basic">
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>BASIC SETTINGS</span><h2>基本配置</h2></div><em>{{ $application->code }}</em></header>
                <div class="panel-body stack-form">
                    <div class="field-grid three-columns">
                        <label class="field">
                            <span>软件代号</span>
                            <input value="{{ $application->code }}" readonly>
                        </label>
                        <label class="field">
                            <span>软件名称</span>
                            <input name="name" value="{{ old('name', $application->name) }}" maxlength="120" required>
                        </label>
                        <label class="field">
                            <span>软件状态</span>
                            <select name="status">
                                @foreach (['active' => '正常开放', 'draft' => '草稿', 'suspended' => '暂停使用', 'archived' => '已归档'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $application->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="field">
                        <span>软件说明</span>
                        <textarea name="description" rows="3" maxlength="500" placeholder="内部备注或软件介绍">{{ old('description', $application->description) }}</textarea>
                    </label>
                    <div class="field-grid">
                        <label class="field">
                            <span>版本号</span>
                            <input name="version_name" value="{{ old('version_name', $application->version_name) }}" maxlength="60" placeholder="例如 v1.0">
                        </label>
                        <label class="field">
                            <span>软件公告</span>
                            <input name="announcement" value="{{ old('announcement', $application->announcement) }}" maxlength="5000" placeholder="客户端需要展示的公告">
                        </label>
                    </div>
                </div>
            </section>

            <section class="dashboard-panel form-panel form-panel-wide verification-panel">
                <header><div><span>VERIFICATION DATA</span><h2>验证数据</h2></div><em>a[2]</em></header>
                <div class="panel-body stack-form">
                    <label class="field verification-data-field">
                        <span>验证数据 <small>客户端返回数组 a[2]</small></span>
                        <textarea name="verification_data" rows="9" maxlength="262144"
                                  placeholder="例如 0x100000，也可以填写地址、文本、JSON 或其他业务数据；不需要时留空。">{{ old('verification_data', $application->verificationData()) }}</textarea>
                        <small>保存后，登录或激活成功时会把这里的内容原样放入 a[2]；支持长文本。</small>
                    </label>
                </div>
            </section>
            </div>

            <div class="admin-form-grid software-config-panel" id="software-panel-limits"
                 role="tabpanel" data-config-panel="limits" hidden>
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>DEVICE & SESSION LIMITS</span><h2>限开控制</h2></div><em>设备与并发</em></header>
                <div class="panel-body stack-form">
                    <input type="hidden" name="bind_device_enabled" value="0">
                    <label class="choice-row">
                        <input type="checkbox" name="bind_device_enabled" value="1"
                               @checked((bool) old('bind_device_enabled', $application->bind_device_enabled))>
                        <span><strong>启用设备绑定</strong><small>首次激活后绑定设备标识。</small></span>
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
                    </div>
                </div>
            </section>
            </div>

            <div class="admin-form-grid software-config-panel" role="tabpanel"
                 data-config-panel="communication" hidden>
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>APPEN SETTINGS</span><h2>接口参数</h2></div><em>{{ $loginApiName }}</em></header>
                <div class="panel-body stack-form">
                    <div class="connection-row">
                        <div>
                            <span>默认登录 API</span>
                            <strong>{{ $loginApiName }}</strong>
                        </div>
                        <small>{{ $application->mode === 'card_login' ? '卡串模式固定使用 .ic' : '账户模式固定使用 .lg' }}</small>
                    </div>
                    <label class="field">
                        <span>时间容差（秒）</span>
                        <input type="number" name="max_clock_skew_seconds" min="30" max="3600"
                               value="{{ old('max_clock_skew_seconds', $application->legacyConfig?->max_clock_skew_seconds ?? 300) }}">
                    </label>
                    <label class="choice-row compact-choice">
                        <input type="hidden" name="legacy_enabled" value="0">
                        <input type="checkbox" name="legacy_enabled" value="1"
                               @checked((bool) old('legacy_enabled', $application->legacyConfig?->legacy_enabled ?? true))>
                        <span><strong>启用 AppEn 兼容接口</strong><small>允许客户端按 {{ $loginApiName }} 调用，后缀随软件模式自动选择。</small></span>
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
