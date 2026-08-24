@extends('admin.layout')

@section('title', $application->name.' · 现卡制作')

@section('content')
    @if($generatedCards !== [])
        <section class="dashboard-panel card-making-workspace-card card-export-workspace"
                 data-card-export-panel
                 data-card-export-status-text="未使用"
                 data-card-export-time="{{ $generatedBatch['generated_at'] ?? now()->format('Y-m-d H:i:s') }}"
                 data-card-export-app="{{ $application->name }}"
                 data-card-export-template-cache-key="mybsphp.card-export-template.{{ $application->public_id }}">
            <header class="card-making-workspace-header">
                <h2>导出充值卡 - 软件：{{ $application->name }}</h2>
            </header>
            <div class="card-export-body">
                <div class="card-export-query-row">
                    <select aria-label="导出时间字段"><option>制作时间</option></select>
                    <input value="{{ $generatedBatch['generated_at'] ?? now()->format('Y-m-d H:i:s') }}" readonly aria-label="制作时间">
                    <input value="{KAID}" aria-label="显示格式" data-card-export-template>
                    <button class="button button-primary card-export-search" type="button" aria-label="按显示格式生成" data-card-export-search>⌕</button>
                </div>
                <div class="card-export-actions" aria-label="导出格式">
                    <button type="button" data-card-export-copy>复制全部</button>
                    <button type="button" data-card-export-format="code">单卡号</button>
                    <button type="button" data-card-export-format="code_secret">卡号+密码</button>
                    <button type="button" data-card-export-format="standard">标准</button>
                    <button type="button" data-card-export-format="dash">卡号----密码</button>
                    <button type="button" data-card-export-format="tab">Tab分隔</button>
                    <button type="button" data-card-export-csv>CSV</button>
                    <button type="button" data-card-export-format="detail">详细</button>
                </div>
                <script type="application/json" data-card-export-json>@json($generatedCards)</script>
                <textarea class="card-export-textarea" data-card-export-output readonly>@foreach($generatedCards as $credential){{ $credential['code'] }}
@endforeach</textarea>
                <div class="card-export-footer">
                    <span data-card-export-status>本次生成 {{ count($generatedCards) }} 张，明文仅在当前结果页展示。</span>
                    <a class="button button-secondary" href="{{ route('admin.software-card-making.show', ['adminPath' => $adminPath, 'application' => $application]) }}"
                       data-workspace-open data-workspace-title="{{ $application->code }}-现卡制作">继续制卡</a>
                </div>
            </div>
        </section>
    @else
        <section class="dashboard-panel card-making-workspace-card">
            <header class="card-making-workspace-header">
                <h2>现卡制作</h2>
            </header>

            @if($cardTypes->isEmpty())
                <div class="empty-activity card-making-empty">
                    <b>!</b>
                    <strong>请先添加充值卡类型</strong>
                    <p>当前软件还没有卡串类型，建立类型后再进行现卡制作。</p>
                    <a class="button button-primary" href="{{ route('admin.software-card-types.index', ['adminPath' => $adminPath, 'application' => $application]) }}"
                       data-workspace-open data-workspace-title="{{ $application->code }}-卡串类型">添加充值卡类型</a>
                </div>
            @else
                <form class="card-making-form" method="post" action="{{ route('admin.card-batches.store', ['adminPath' => $adminPath]) }}">
                    @csrf
                    <div class="card-type-form-rows card-making-form-rows">
                        <div class="card-type-form-row card-type-context-row">
                            <strong>所属软件</strong>
                            <div><b>{{ $application->name }}</b> <span>软件代号：{{ $application->code }}</span></div>
                            <small>本次卡密只归属于该软件</small>
                        </div>
                        <label class="card-type-form-row">
                            <strong>卡串类型</strong>
                            <select name="card_type_id" required>
                                <option value="">请选择制作类型…</option>
                                @foreach($cardTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('card_type_id') === (string) $type->id)>
                                        {{ $type->name }} =&gt; {{ $type->duration_value }}{{ ['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'][$type->duration_unit] ?? '天' }}
                                    </option>
                                @endforeach
                            </select>
                            <small>制卡类型</small>
                        </label>
                        <div class="card-type-form-row">
                            <strong>制卡方式</strong>
                            <div class="making-radio-options">
                                <label><input type="radio" name="generation_mode" value="automatic" checked> 自动生成</label>
                                <label><input type="radio" name="generation_mode" value="import" disabled> 导入卡号</label>
                            </div>
                            <small>自动生成或使用自定义卡号</small>
                        </div>
                        <label class="card-type-form-row">
                            <strong>制作数量</strong>
                            <input type="number" name="quantity" min="1" max="500" value="{{ old('quantity', 1) }}" required>
                            <small>自动生成时使用的制卡数量，单批最多 500 张</small>
                        </label>
                        <label class="card-type-form-row">
                            <strong>制卡备注</strong>
                            <input name="note" maxlength="500" value="{{ old('note') }}" placeholder="可空">
                            <small>管理员可见</small>
                        </label>
                        <label class="card-type-form-row">
                            <strong>制卡者</strong>
                            <input name="maker_reference" maxlength="120" value="{{ old('maker_reference') }}" placeholder="不指定留空为当前管理员">
                            <small>可填写指定用户名或 UID；留空记录当前管理员</small>
                        </label>
                        <div class="form-submit-bar embedded-submit-bar card-making-submit-bar">
                            <span>卡号和密码按所选卡串类型生成。</span>
                            <button class="button button-primary" type="submit">确认制卡</button>
                        </div>
                    </div>
                </form>
            @endif
        </section>
    @endif
@endsection
