@extends('admin.layout')

@php($editing = $application !== null)
@section('title', $editing ? '软件配置' : '添加新软件')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">{{ $editing ? 'SOFTWARE SETTINGS' : 'NEW SOFTWARE' }}</p>
            <h1>{{ $editing ? '软件配置' : '添加新软件' }}</h1>
            <p>{{ $editing ? '验证数据会直接作为客户端返回数组的 a[2]。' : '先建立软件，其他参数在创建完成后进入配置页设置。' }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回列表</a>
    </header>

    @if (session('client_secret'))
        <section class="secret-panel">
            <div><span>CLIENT SECRET · 仅展示本次</span><strong>{{ session('client_secret') }}</strong></div>
            <button type="button" data-copy="{{ session('client_secret') }}">复制密钥</button>
        </section>
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
                            <input name="code" value="{{ old('code') }}" maxlength="40"
                                   placeholder="建议使用统一的 8 位数字" required>
                            <small>可使用数字、字母、下划线和短横线，创建后保持不变。</small>
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
        <form class="admin-form-grid" method="post"
              action="{{ route('admin.applications.update', ['adminPath' => $adminPath, 'application' => $application]) }}">
            @csrf
            @method('PUT')

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

            <section class="dashboard-panel form-panel">
                <header><div><span>DEVICE</span><h2>设备设置</h2></div><em>可选</em></header>
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

            <section class="dashboard-panel form-panel">
                <header><div><span>APPEN</span><h2>接口兼容配置</h2></div><em>login.ic</em></header>
                <div class="panel-body stack-form">
                    <label class="field">
                        <span>登录 API 名称</span>
                        <input name="login_api_name" value="{{ old('login_api_name', $application->legacyConfig?->login_api_name ?? 'login.ic') }}" required>
                    </label>
                    <label class="field">
                        <span>时间容差（秒）</span>
                        <input type="number" name="max_clock_skew_seconds" min="30" max="3600"
                               value="{{ old('max_clock_skew_seconds', $application->legacyConfig?->max_clock_skew_seconds ?? 300) }}">
                    </label>
                    <label class="choice-row compact-choice">
                        <input type="hidden" name="legacy_enabled" value="0">
                        <input type="checkbox" name="legacy_enabled" value="1"
                               @checked((bool) old('legacy_enabled', $application->legacyConfig?->legacy_enabled ?? true))>
                        <span><strong>启用 AppEn 兼容接口</strong><small>允许客户端按 login.ic 调用。</small></span>
                    </label>
                </div>
            </section>

            <div class="form-submit-bar">
                <span>验证数据修改后会立即用于新的成功响应。</span>
                <button class="button button-primary" type="submit">确认保存</button>
            </div>
        </form>
    @endif
@endsection
