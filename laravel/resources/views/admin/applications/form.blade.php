@extends('admin.layout')

@php($editing = $application !== null)
@section('title', $editing ? '应用配置' : '添加应用')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">{{ $editing ? 'APPLICATION SETTINGS' : 'NEW APPLICATION' }}</p>
            <h1>{{ $editing ? '应用配置' : '添加新软件' }}</h1>
            <p>{{ $editing ? '更新授权响应、设备绑定和 Legacy 参数。' : '建立应用后即可创建卡种、生成卡密并手动发卡。' }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回列表</a>
    </header>

    @if (session('client_secret'))
        <section class="secret-panel">
            <div><span>CLIENT SECRET · 仅展示本次</span><strong>{{ session('client_secret') }}</strong></div>
            <button type="button" data-copy="{{ session('client_secret') }}">复制密钥</button>
        </section>
    @endif

    <form class="admin-form-grid" method="post"
          action="{{ $editing
              ? route('admin.applications.update', ['adminPath' => $adminPath, 'application' => $application])
              : route('admin.applications.store', ['adminPath' => $adminPath]) }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="dashboard-panel form-panel">
            <header><div><span>BASIC</span><h2>基础信息</h2></div><em>必填</em></header>
            <div class="panel-body stack-form">
                <div class="field-grid">
                    <label class="field">
                        <span>应用代号</span>
                        <input name="code" value="{{ old('code', $application?->code) }}" maxlength="40"
                               placeholder="例如 game_ios" {{ $editing ? 'readonly' : '' }} required>
                    </label>
                    <label class="field">
                        <span>应用名称</span>
                        <input name="name" value="{{ old('name', $application?->name) }}" maxlength="120"
                               placeholder="例如 我的游戏 iOS" required>
                    </label>
                </div>
                <label class="field">
                    <span>说明</span>
                    <textarea name="description" rows="3" maxlength="500" placeholder="内部备注和用途">{{ old('description', $application?->description) }}</textarea>
                </label>
                <div class="field-grid">
                    <label class="field">
                        <span>授权模式</span>
                        <select name="mode" {{ $editing ? 'disabled' : '' }}>
                            <option value="card_login" @selected(old('mode', $application?->mode ?? 'card_login') === 'card_login')>一次性卡密登录</option>
                            <option value="account_recharge" @selected(old('mode', $application?->mode) === 'account_recharge')>账户注册与充值</option>
                        </select>
                        @if ($editing)<input type="hidden" name="mode" value="{{ $application->mode }}">@endif
                    </label>
                    <label class="field">
                        <span>权益类型</span>
                        <select name="entitlement_kind" {{ $editing ? 'disabled' : '' }}>
                            <option value="duration" @selected(old('entitlement_kind', $application?->entitlement_kind ?? 'duration') === 'duration')>按时长</option>
                            <option value="points" @selected(old('entitlement_kind', $application?->entitlement_kind) === 'points')>按点数</option>
                            <option value="fixed_expiry" @selected(old('entitlement_kind', $application?->entitlement_kind) === 'fixed_expiry')>固定到期时间</option>
                        </select>
                        @if ($editing)<input type="hidden" name="entitlement_kind" value="{{ $application->entitlement_kind }}">@endif
                    </label>
                </div>
                <label class="field">
                    <span>状态</span>
                    <select name="status">
                        @foreach (['draft' => '草稿', 'active' => '启用', 'suspended' => '停用', 'archived' => '归档'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $application?->status ?? 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="dashboard-panel form-panel">
            <header><div><span>RESPONSE</span><h2>授权返回与设备</h2></div><em>arr[2] / arr[8]</em></header>
            <div class="panel-body stack-form">
                <label class="field">
                    <span>核心业务值模式</span>
                    <select name="payload_mode">
                        <option value="constant" @selected(old('payload_mode', $application?->payload_mode ?? 'constant') === 'constant')>返回配置值</option>
                        <option value="empty" @selected(old('payload_mode', $application?->payload_mode) === 'empty')>返回空值</option>
                    </select>
                </label>
                <label class="field">
                    <span>核心业务值 <small>Legacy arr[2]</small></span>
                    <input name="payload_value_ciphertext"
                           value="{{ old('payload_value_ciphertext', $application?->payload_value_ciphertext ?? 'A1B2C3D4E5') }}"
                           maxlength="255" placeholder="10 位业务值、地址或留空">
                </label>
                <input type="hidden" name="bind_device_enabled" value="0">
                <label class="choice-row">
                    <input type="checkbox" name="bind_device_enabled" value="1"
                           @checked((bool) old('bind_device_enabled', $application?->bind_device_enabled ?? true))>
                    <span><strong>启用设备绑定</strong><small>首次激活绑定设备，其他设备返回冲突。</small></span>
                </label>
                <div class="field-grid">
                    <label class="field">
                        <span>最多绑定设备</span>
                        <input type="number" name="max_bound_devices" min="1" max="20"
                               value="{{ old('max_bound_devices', $application?->max_bound_devices ?? 1) }}" required>
                    </label>
                    <label class="field">
                        <span>最大并发会话</span>
                        <input type="number" name="max_concurrent_sessions" min="1" max="100"
                               value="{{ old('max_concurrent_sessions', $application?->max_concurrent_sessions ?? 1) }}" required>
                    </label>
                </div>
                <div class="field-grid">
                    <label class="field">
                        <span>版本名称</span>
                        <input name="version_name" value="{{ old('version_name', $application?->version_name) }}" maxlength="60" placeholder="1.0.0">
                    </label>
                    <label class="field">
                        <span>公告</span>
                        <input name="announcement" value="{{ old('announcement', $application?->announcement) }}" maxlength="5000" placeholder="客户端公告">
                    </label>
                </div>
            </div>
        </section>

        @if ($editing)
            <section class="dashboard-panel form-panel form-panel-wide">
                <header><div><span>LEGACY</span><h2>AppEn 兼容参数</h2></div><em>login.ic</em></header>
                <div class="panel-body">
                    <div class="field-grid three-columns">
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
                            <span><strong>启用 Legacy</strong><small>允许 AppEn.php 调用链。</small></span>
                        </label>
                    </div>
                </div>
            </section>
        @endif

        <div class="form-submit-bar">
            <span>{{ $editing ? '修改将写入审计日志。' : '创建后会生成一次性客户端密钥。' }}</span>
            <button class="button button-primary" type="submit">{{ $editing ? '保存应用配置' : '创建应用' }}</button>
        </div>
    </form>
@endsection
