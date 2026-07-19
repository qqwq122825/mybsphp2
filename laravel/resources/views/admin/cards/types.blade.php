@extends('admin.layout')

@section('title', $application->name.' · 卡串类型')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">CARD TYPES</p>
            <h1>卡串类型 · {{ $application->name }}</h1>
            <p>当前页是软件内部工作页，只管理代号 {{ $application->code }} 的充值卡类型。</p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回软件管理</a>
    </header>

    <form class="dashboard-panel form-panel card-type-page-form" method="post"
          action="{{ route('admin.card-types.store', ['adminPath' => $adminPath]) }}">
        @csrf
        <input type="hidden" name="application_id" value="{{ $application->id }}">
        <header><div><span>ADD CARD TYPE</span><h2>添加充值卡类型</h2></div><em>权益规则</em></header>
        <div class="panel-body card-type-form-rows">
            <div class="card-type-form-row card-type-context-row">
                <strong>充值卡软件</strong>
                <div><b>{{ $application->name }}</b> <span>（代号：{{ $application->code }}）</span></div>
                <small>当前类型只归属于该软件</small>
            </div>
            <label class="card-type-form-row">
                <strong>充值卡别名</strong>
                <input name="name" value="{{ old('name') }}" placeholder="例如贵宾卡、年卡" required>
                <small>后台显示的充值卡类型名称</small>
            </label>
            <label class="card-type-form-row">
                <strong>类型代号</strong>
                <input name="code" value="{{ old('code') }}" placeholder="例如 year、month30" required>
                <small>字母开头，可使用数字、横线和下划线</small>
            </label>
            <label class="card-type-form-row">
                <strong>权益类型</strong>
                <select name="benefit_kind">
                    <option value="duration" @selected(old('benefit_kind', 'duration') === 'duration')>按时长</option>
                    <option value="points" @selected(old('benefit_kind') === 'points')>按点数</option>
                    <option value="fixed_expiry" @selected(old('benefit_kind') === 'fixed_expiry')>固定到期</option>
                </select>
                <small>决定卡密首次激活后获得的权益</small>
            </label>
            <label class="card-type-form-row">
                <strong>充值卡期限</strong>
                <div class="inline-unit-input"><input type="number" name="duration_days" min="1" max="3650" value="{{ old('duration_days', 30) }}"><span>天</span></div>
                <small>按时长模式使用，1 天等于 86400 秒</small>
            </label>
            <label class="card-type-form-row">
                <strong>充值点数</strong>
                <input type="number" name="points_amount" min="1" value="{{ old('points_amount') }}" placeholder="按点数模式填写">
                <small>按点数模式使用</small>
            </label>
            <label class="card-type-form-row">
                <strong>固定到期时间</strong>
                <input type="datetime-local" name="fixed_expires_at" value="{{ old('fixed_expires_at') }}">
                <small>固定到期模式使用</small>
            </label>
            <label class="card-type-form-row">
                <strong>价格</strong>
                <input type="number" step="0.01" min="0" name="face_price" value="{{ old('face_price', 0) }}">
                <small>充值卡预定销售价格，单位为元</small>
            </label>
            <label class="card-type-form-row">
                <strong>代理价格</strong>
                <input type="number" step="0.01" min="0" name="agent_price" value="{{ old('agent_price', 0) }}">
                <small>代理默认结算价格，单位为元</small>
            </label>
            <label class="card-type-form-row">
                <strong>开头字符</strong>
                <input name="code_prefix" maxlength="8" value="{{ old('code_prefix', 'BS') }}" required>
                <small>卡号生成时使用的前缀，最多 8 位字母或数字</small>
            </label>
            <label class="card-type-form-row">
                <strong>卡号长度</strong>
                <input type="number" name="code_length" min="16" max="40" value="{{ old('code_length', 20) }}" required>
                <small>包含开头字符，建议 20–35 位</small>
            </label>
            <label class="card-type-form-row">
                <strong>密码长度</strong>
                <input type="number" name="secret_length" min="6" max="24" value="{{ old('secret_length', 8) }}" required>
                <small>卡密附加密码长度</small>
            </label>
            <div class="form-submit-bar embedded-submit-bar">
                <span>保存后可立即进入现卡制作。</span>
                <button class="button button-primary" type="submit">确认保存</button>
            </div>
        </div>
    </form>

    <section class="dashboard-panel data-panel card-type-list-panel">
        <header><div><span>EXISTING TYPES</span><h2>已有充值卡类型</h2></div><em>{{ $cardTypes->count() }} 个</em></header>
        @if($cardTypes->isEmpty())
            <div class="empty-activity"><b>◇</b><strong>还没有类型</strong><p>保存第一个类型后即可进入现卡制作。</p></div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>类型名称</th><th>代号</th><th>权益</th><th>面值</th><th>代理默认价</th><th>已制作</th><th>状态</th></tr></thead>
                    <tbody>
                    @foreach($cardTypes as $type)
                        <tr>
                            <td><strong>{{ $type->name }}</strong></td>
                            <td>{{ $type->code }}</td>
                            <td>
                                @if($type->benefit_kind === 'duration')
                                    {{ number_format(($type->duration_seconds ?? 0) / 86400, 0) }} 天
                                @elseif($type->benefit_kind === 'points')
                                    {{ $type->points_amount }} 点
                                @else
                                    {{ $type->fixed_expires_at?->format('Y-m-d H:i') }}
                                @endif
                            </td>
                            <td>¥{{ number_format($type->face_price_cents / 100, 2) }}</td>
                            <td>¥{{ number_format($type->default_agent_price_cents / 100, 2) }}</td>
                            <td>{{ $type->cards_count }}</td>
                            <td><span class="status-tag status-{{ $type->status }}">{{ $type->status }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
