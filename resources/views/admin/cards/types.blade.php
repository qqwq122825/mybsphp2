@extends('admin.layout')

@section('title', $application->name.' · 卡串类型')

@section('content')
    @php
        $type = $editingType;
        $units = ['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'];
        $charsets = [
            'mixed' => '默认数字+大写+小写组合', 'digits' => '仅数字', 'uppercase' => '仅大写字母',
            'lowercase' => '仅小写字母', 'digits_uppercase' => '数字+大写字母', 'digits_lowercase' => '数字+小写字母',
        ];
        $cardTypeListUrl = route('admin.software-card-types.index', ['adminPath' => $adminPath, 'application' => $application]);
    @endphp

    @if($showTypeForm)
        <section class="dashboard-panel card-type-workspace-card" data-card-type-editor-dialog>
            <header class="card-type-workspace-header card-type-editor-header">
                <h2>{{ $type ? '编辑充值卡类型' : '添加充值卡类型' }}</h2>
                <a class="button" data-workspace-follow href="{{ $cardTypeListUrl }}">返回列表</a>
            </header>
            <form class="card-type-page-form" method="post"
                  action="{{ $type ? route('admin.card-types.update', ['adminPath' => $adminPath, 'cardType' => $type]) : route('admin.card-types.store', ['adminPath' => $adminPath]) }}">
                @csrf
                @if($type) @method('PUT') @endif
                <input type="hidden" name="application_id" value="{{ $application->id }}">
                <div class="card-type-form-rows">
                    <div class="card-type-form-row card-type-context-row">
                        <strong>充值卡软件</strong>
                        <div><b>{{ $application->name }}</b> <span>（代号：{{ $application->code }}）</span></div>
                        <small>当前类型归属于该软件</small>
                    </div>
                    <label class="card-type-form-row"><strong>充值卡别名</strong>
                        <input name="name" value="{{ old('name', $type?->name) }}" required><small>充值卡名称，如：贵宾卡、年卡</small></label>
                    <label class="card-type-form-row"><strong>开头字符</strong>
                        <input name="code_prefix" maxlength="10" value="{{ old('code_prefix', $type?->code_prefix) }}"><small>制卡开头字符，如：Z、j、GG，10 位字符内</small></label>
                    <label class="card-type-form-row"><strong>备注</strong>
                        <input name="note" maxlength="500" value="{{ old('note', $type?->note) }}"><small>卡类型内部备注，仅后台展示</small></label>
                    <label class="card-type-form-row"><strong>排序</strong>
                        <input type="number" name="sort_order" min="0" max="4294967295" value="{{ old('sort_order', $type?->sort_order ?? 0) }}" required><small>数字越小越靠前，建议 10、20、30</small></label>
                    <label class="card-type-form-row"><strong>充值卡期限</strong>
                        <div class="inline-unit-input"><input type="number" name="duration_value" min="1" value="{{ old('duration_value', $type?->duration_value ?? 1) }}" required>
                            <select name="duration_unit" aria-label="充值卡期限单位">@foreach($units as $value => $label)<option value="{{ $value }}" @selected(old('duration_unit', $type?->duration_unit ?? 'day') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <small>1 分钟等于 60 秒</small></label>
                    <label class="card-type-form-row"><strong>价格</strong>
                        <input type="number" step="0.01" min="-1" name="face_price" value="{{ old('face_price', $type ? $type->face_price_cents / 100 : 1) }}" required><small>设置 -1 时前台隐藏并禁止购买</small></label>
                    <label class="card-type-form-row"><strong>代理价格</strong>
                        <input type="number" step="0.01" min="-1" name="agent_price" value="{{ old('agent_price', $type ? $type->default_agent_price_cents / 100 : 1) }}" required><small>设置 -1 时代理端隐藏并禁止购买</small></label>
                    <label class="card-type-form-row"><strong>余额充值</strong>
                        <input type="number" step="0.01" name="balance_recharge" value="{{ old('balance_recharge', $type ? $type->balance_recharge_cents / 100 : 0) }}" required><small>不需要时填 0，支持负数</small></label>
                    <label class="card-type-form-row"><strong>卡号长度</strong>
                        <input type="number" name="code_length" min="10" max="64" value="{{ old('code_length', $type?->code_length ?? 25) }}" required><small>充值卡串长度设置，一般建议 35 位</small></label>
                    <label class="card-type-form-row"><strong>卡号字符类型</strong>
                        @include('admin.cards.partials.charset-select', ['name' => 'code_charset', 'selected' => $type?->code_charset ?? 'mixed'])<small>充值卡组成，建议使用默认组合</small></label>
                    <label class="card-type-form-row"><strong>卡密长度</strong>
                        <input type="number" name="secret_length" min="0" max="64" value="{{ old('secret_length', $type?->secret_length ?? 0) }}" required>
                        <small>填 0 表示无密码；建议 8-16 位</small></label>
                    <label class="card-type-form-row"><strong>卡密字符类型</strong>
                        @include('admin.cards.partials.charset-select', ['name' => 'secret_charset', 'selected' => old('secret_charset', $type?->secret_charset ?? 'mixed')])
                        <small>卡密组成，建议使用默认组合</small></label>
                    <label class="card-type-form-row"><strong>账号同时在线数</strong>
                        <input type="number" name="account_concurrency_limit" min="-1" max="32767" value="{{ old('account_concurrency_limit', $type?->account_concurrency_limit ?? 0) }}" required><small>0 使用软件默认，-1 不限制同时在线数</small></label>
                    <label class="card-type-form-row"><strong>机器同时使用量</strong>
                        <input type="number" name="machine_concurrency_limit" min="-1" max="32767" value="{{ old('machine_concurrency_limit', $type?->machine_concurrency_limit ?? 0) }}" required><small>0 使用软件默认，-1 不限制设备数量</small></label>
                    <div class="form-submit-bar embedded-submit-bar">
                        <a class="button" data-workspace-follow href="{{ $cardTypeListUrl }}">取消</a>
                        <button class="button button-primary" type="submit">确认保存</button>
                    </div>
                </div>
            </form>
        </section>
    @else
        <section class="dashboard-panel card-type-workspace-card">
            <header class="card-type-workspace-header">
                <h2>充值卡类型</h2>
            </header>

            <form id="quick-card-type-form" method="post" action="{{ route('admin.card-types.quick', ['adminPath' => $adminPath]) }}">@csrf<input type="hidden" name="application_id" value="{{ $application->id }}"></form>
            <form id="card-type-create-form" method="get" action="{{ $cardTypeListUrl }}">
                <input type="hidden" name="create" value="1">
            </form>
            <div class="bsphp-type-list-wrap uses-admin-data-table">
                <x-admin.table-frame frame-class="card-type-table-component" scroll-class="bsphp-type-table-scroll">
                    <x-slot:toolbar>
                        <div>
                        <button class="button card-type-delete-button" type="submit" form="card-type-delete-form"
                                onclick="return confirm('将删除所选卡串类型本身，并清理该类型下未激活的库存卡和已发出卡；已激活或已使用的卡会保留并继续支持查询。确定继续吗？')">删除选中</button>
                        <button class="button button-primary" type="submit" form="card-type-create-form">添加新充值卡类型</button>
                        <button class="button bsphp-type-quick" type="submit" form="quick-card-type-form">快速添加模拟卡类型</button>
                        </div>
                        <span>{{ $cardTypes->count() }} 条</span>
                    </x-slot:toolbar>
                    <form id="card-type-delete-form" method="post" action="{{ route('admin.card-types.delete', ['adminPath' => $adminPath]) }}">
                        @csrf @method('DELETE')
                        <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <table class="bsphp-type-table">
                        <thead><tr>
                            <th><input type="checkbox" data-card-select-all aria-label="全选"></th><th>编号</th><th>排序</th><th>软件名称</th><th>代号</th><th>卡串名称</th><th>备注</th><th>期限</th>
                            <th>卡号长度</th><th>卡号组成</th><th>价格</th><th>代理价格</th><th>账号多开</th><th>机器用量</th><th>操作</th>
                        </tr></thead>
                        <tbody>
                        @forelse($cardTypes as $cardType)
                            <tr>
                                <td><input type="checkbox" name="card_type_ids[]" value="{{ $cardType->id }}" data-card-select></td>
                                <td>{{ $cardType->id }}</td><td>{{ $cardType->sort_order }}</td><td>{{ $application->name }}</td><td>{{ $cardType->code }}</td>
                                <td>{{ $cardType->name }}</td><td>{{ $cardType->note ?: '—' }}</td><td>{{ $cardType->duration_value }}{{ $units[$cardType->duration_unit] ?? '天' }}</td>
                                <td>{{ $cardType->code_length }}</td><td>{{ $charsets[$cardType->code_charset] ?? $charsets['mixed'] }}</td>
                                <td>{{ number_format($cardType->face_price_cents / 100, 2) }}</td><td>{{ number_format($cardType->default_agent_price_cents / 100, 2) }}</td>
                                <td>{{ $cardType->account_concurrency_limit ?: '默认软件配置' }}</td><td>{{ $cardType->machine_concurrency_limit ?: '默认软件配置' }}</td>
                                <td><a class="bsphp-detail-button" data-workspace-follow href="{{ route('admin.software-card-types.index', ['adminPath' => $adminPath, 'application' => $application, 'edit' => $cardType->public_id]) }}">编辑</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="bsphp-table-empty">无数据</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </form>
                    <x-slot:footer><button type="button" disabled>‹</button><b>1</b><button type="button" disabled>›</button><span>共 {{ $cardTypes->count() }} 条</span><select><option>10 条/页</option></select></x-slot:footer>
                </x-admin.table-frame>
            </div>
        </section>
    @endif
@endsection
