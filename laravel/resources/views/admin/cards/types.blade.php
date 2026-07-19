@extends('admin.layout')

@section('title', $application->name.' · 卡串类型')

@section('content')
    <section class="dashboard-panel card-type-workspace-card">
        <header class="card-type-workspace-header">
            <h2>添加充值卡类型</h2>
        </header>

        <form class="card-type-page-form" method="post"
              action="{{ route('admin.card-types.store', ['adminPath' => $adminPath]) }}">
            @csrf
            <input type="hidden" name="application_id" value="{{ $application->id }}">
            <div class="card-type-form-rows">
                <div class="card-type-form-row card-type-context-row">
                    <strong>充值卡软件</strong>
                    <div><b>{{ $application->name }}</b> <span>（代号：{{ $application->code }}）</span></div>
                    <small>当前类型归属于该软件</small>
                </div>
                <label class="card-type-form-row">
                    <strong>充值卡别名</strong>
                    <input name="name" value="{{ old('name') }}" required>
                    <small>充值卡名称 如：贵宾卡、年卡</small>
                </label>
                <label class="card-type-form-row">
                    <strong>开头字符</strong>
                    <input name="code_prefix" maxlength="10" value="{{ old('code_prefix') }}">
                    <small>充值卡制作卡开头字符，如：Z、j、GG，10 位字符内</small>
                </label>
                <label class="card-type-form-row">
                    <strong>备注</strong>
                    <input name="note" maxlength="500" value="{{ old('note') }}">
                    <small>卡类型内部备注，仅后台展示</small>
                </label>
                <label class="card-type-form-row">
                    <strong>排序</strong>
                    <input type="number" name="sort_order" min="0" max="4294967295" value="{{ old('sort_order', 0) }}" required>
                    <small>数字越小越靠前，建议 10、20、30 这样设置</small>
                </label>
                <label class="card-type-form-row">
                    <strong>充值卡期限</strong>
                    <div class="inline-unit-input">
                        <input type="number" name="duration_value" min="1" value="{{ old('duration_value', 1) }}" required>
                        <select name="duration_unit" aria-label="充值卡期限单位">
                            <option value="minute" @selected(old('duration_unit') === 'minute')>分钟</option>
                            <option value="hour" @selected(old('duration_unit') === 'hour')>小时</option>
                            <option value="day" @selected(old('duration_unit', 'day') === 'day')>天</option>
                            <option value="month" @selected(old('duration_unit') === 'month')>月</option>
                            <option value="year" @selected(old('duration_unit') === 'year')>年</option>
                        </select>
                    </div>
                    <small>1 分钟等于 60 秒</small>
                </label>
                <label class="card-type-form-row">
                    <strong>价格</strong>
                    <input type="number" step="0.01" min="-1" name="face_price" value="{{ old('face_price', 1) }}" required>
                    <small>充值预定销售价格，设置 -1 时前台隐藏并禁止购买</small>
                </label>
                <label class="card-type-form-row">
                    <strong>代理价格</strong>
                    <input type="number" step="0.01" min="-1" name="agent_price" value="{{ old('agent_price', 1) }}" required>
                    <small>代理预定销售价格，设置 -1 时前台隐藏并禁止购买</small>
                </label>
                <label class="card-type-form-row">
                    <strong>余额充值</strong>
                    <input type="number" step="0.01" name="balance_recharge" value="{{ old('balance_recharge', 0) }}" required>
                    <small>给账号余额充值；不需要时填 0，支持负数，循环充值慎用</small>
                </label>
                <label class="card-type-form-row">
                    <strong>卡号长度</strong>
                    <input type="number" name="code_length" min="10" max="64" value="{{ old('code_length', 25) }}" required>
                    <small>充值卡卡串长度设置，一般建议 35 位</small>
                </label>
                <label class="card-type-form-row">
                    <strong>卡号字符类型</strong>
                    @include('admin.cards.partials.charset-select', ['name' => 'code_charset'])
                    <small>充值卡组成，建议使用默认组合</small>
                </label>
                <label class="card-type-form-row">
                    <strong>密码长度</strong>
                    <input type="number" name="secret_length" min="0" max="64" value="{{ old('secret_length', 0) }}" required>
                    <small>可设置 0（无需密码）</small>
                </label>
                <label class="card-type-form-row">
                    <strong>密码字符类型</strong>
                    @include('admin.cards.partials.charset-select', ['name' => 'secret_charset'])
                    <small>充值卡密码组成，建议使用默认组合</small>
                </label>
                <label class="card-type-form-row">
                    <strong>账号同时在线数</strong>
                    <input type="number" name="account_concurrency_limit" min="-1" max="32767" value="{{ old('account_concurrency_limit', 0) }}" required>
                    <small>0=使用系统默认（软件配置 &gt; 限开控制），-1 关闭此项限制</small>
                </label>
                <label class="card-type-form-row">
                    <strong>机器同时使用量</strong>
                    <input type="number" name="machine_concurrency_limit" min="-1" max="32767" value="{{ old('machine_concurrency_limit', 0) }}" required>
                    <small>0=使用系统默认（软件配置 &gt; 限开控制），-1 关闭此项限制</small>
                </label>
                <div class="form-submit-bar embedded-submit-bar">
                    <span>内部识别值由系统自动生成，保存后可进入现卡制作。</span>
                    <button class="button button-primary" type="submit">确认保存</button>
                </div>
            </div>
        </form>

        <section class="card-type-list-panel">
            <header><h2>已有充值卡类型</h2><em>{{ $cardTypes->count() }} 个</em></header>
            @if($cardTypes->isEmpty())
                <div class="empty-activity"><b>◇</b><strong>还没有类型</strong><p>保存第一个类型后即可进入现卡制作。</p></div>
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>类型名称</th><th>开头字符</th><th>期限</th><th>价格</th><th>排序</th><th>已制作</th><th>状态</th></tr></thead>
                        <tbody>
                        @foreach($cardTypes as $type)
                            <tr>
                                <td><strong>{{ $type->name }}</strong></td>
                                <td>{{ $type->code_prefix }}</td>
                                <td>{{ $type->duration_value }} {{ ['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'][$type->duration_unit] ?? '天' }}</td>
                                <td>¥{{ number_format($type->face_price_cents / 100, 2) }}</td>
                                <td>{{ $type->sort_order }}</td>
                                <td>{{ $type->cards_count }}</td>
                                <td><span class="status-tag status-{{ $type->status }}">{{ $type->status }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </section>
@endsection
