@extends('admin.layout')

@section('title', '充值卡批量维护')

@section('content')
    @php($selectedMaintenanceAction = old('action', request('action', 'unused')))
    <section class="dashboard-panel card-maintenance-panel">
        <header><div><h2>充值卡批量维护</h2></div></header>

        <form method="post" action="{{ route('admin.cards.maintenance.run', ['adminPath' => $adminPath]) }}" data-maintenance-form>
            @csrf
            <div class="card-maintenance-rows">
                <div class="card-maintenance-mode">
                    <label><input type="radio" name="selection_mode" value="ids" @checked(old('selection_mode') === 'ids')><b>A. ID 批量处理</b><span>通过索引 ID 精确维护</span></label>
                    <label><input type="radio" name="selection_mode" value="filter" @checked(old('selection_mode', 'filter') === 'filter')><b>B. 筛选批量处理</b><span>通过软件和状态范围维护</span></label>
                </div>

                <div class="card-type-form-row maintenance-id-row" data-mode-row="ids">
                    <strong>索引 ID</strong>
                    <input name="id_list" value="{{ old('id_list') }}" placeholder="例如：1,2,8-20">
                    <small>支持逗号、空格和连续范围；卡号不在这里填写。</small>
                </div>

                <div class="card-type-form-row" data-mode-row="filter">
                    <strong>选择软件</strong>
                    <select name="application_id">
                        <option value="">全部软件</option>
                        @foreach($applications as $application)
                            <option value="{{ $application->id }}" @selected((string) old('application_id') === (string) $application->id)>{{ $application->name }}（{{ $application->code }}）</option>
                        @endforeach
                    </select>
                    <small>选择要维护的软件。</small>
                </div>

                <div class="card-type-form-row" data-mode-row="filter">
                    <strong>维护范围</strong>
                    <select name="scope" data-maintenance-scope>
                        <option value="all" @selected(old('scope', 'all') === 'all')>全部卡串</option>
                        <option value="frozen" @selected(old('scope') === 'frozen')>状态：冻结</option>
                        <option value="normal" @selected(old('scope') === 'normal')>状态：正常</option>
                        <option value="unused" @selected(old('scope') === 'unused')>未使用</option>
                        <option value="used" @selected(old('scope') === 'used')>已使用</option>
                        <option value="card_type" @selected(old('scope') === 'card_type')>指定卡类型</option>
                    </select>
                    <small>先缩小数据范围，再执行维护功能。</small>
                </div>

                <div class="card-type-form-row" data-card-type-row hidden>
                    <strong>卡类型</strong>
                    <select name="card_type_id">
                        <option value="">请选择</option>
                        @foreach($cardTypes as $type)
                            <option value="{{ $type->id }}" data-application="{{ $type->application_id }}" @selected((string) old('card_type_id') === (string) $type->id)>
                                {{ $type->application->name }} · {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    <small>仅处理该卡类型下的卡串。</small>
                </div>

                <div class="card-type-form-row">
                    <strong>选择维护功能</strong>
                    <select name="action" data-maintenance-action>
                        <option value="unused" @selected($selectedMaintenanceAction === 'unused')>设置未使用</option>
                        <option value="used" @selected($selectedMaintenanceAction === 'used')>设置已使用</option>
                        <option value="freeze" @selected($selectedMaintenanceAction === 'freeze')>设置冻结</option>
                        <option value="unfreeze" @selected($selectedMaintenanceAction === 'unfreeze')>设置正常</option>
                        <option value="set_duration" @selected($selectedMaintenanceAction === 'set_duration')>设置充值天数为 N</option>
                        <option value="set_sale_price" @selected($selectedMaintenanceAction === 'set_sale_price')>设置销售价格为 N</option>
                        <option value="set_agent_price" @selected($selectedMaintenanceAction === 'set_agent_price')>设置代理价格为 N</option>
                        <option value="set_created_at" @selected($selectedMaintenanceAction === 'set_created_at')>设置制卡时间为 N</option>
                        <option value="delete" @selected($selectedMaintenanceAction === 'delete')>删除卡串（设为已作废）</option>
                    </select>
                    <small data-action-help>选择要执行的结果功能。</small>
                </div>

                <div class="card-type-form-row" data-action-value-row hidden>
                    <strong>维护值 N</strong>
                    <input name="action_value" value="{{ old('action_value') }}" data-action-value>
                    <small data-action-value-help></small>
                </div>

                <div class="card-maintenance-warning">
                    <strong>注意</strong>
                    <span>批量操作前请备份数据。“冻结卡串”只阻止未激活卡继续验证，已激活用户需在软件用户列表中冻结。</span>
                </div>

                <label class="card-maintenance-confirm">
                    <input type="checkbox" name="confirmed" value="1" @checked(old('confirmed')) required>
                    <span>我已核对处理范围和维护功能</span>
                </label>
            </div>

            <footer class="card-maintenance-footer">
                <div><strong>操作摘要</strong><span data-maintenance-summary></span></div>
                <button class="button button-primary" type="submit">确认操作</button>
            </footer>
        </form>
    </section>

    <script>
    (() => {
        const form = document.querySelector('[data-maintenance-form]');
        if (!form) return;
        const modeInputs = [...form.querySelectorAll('[name="selection_mode"]')];
        const modeRows = [...form.querySelectorAll('[data-mode-row]')];
        const application = form.querySelector('[name="application_id"]');
        const scope = form.querySelector('[data-maintenance-scope]');
        const typeRow = form.querySelector('[data-card-type-row]');
        const typeSelect = form.querySelector('[name="card_type_id"]');
        const action = form.querySelector('[data-maintenance-action]');
        const valueRow = form.querySelector('[data-action-value-row]');
        const valueInput = form.querySelector('[data-action-value]');
        const valueHelp = form.querySelector('[data-action-value-help]');
        const summary = form.querySelector('[data-maintenance-summary]');
        const valueConfig = {
            set_duration: ['填写天数，例如 30', '30'],
            set_sale_price: ['填写销售价格，例如 68', '68'],
            set_agent_price: ['填写代理价格，例如 50', '50'],
            set_created_at: ['时间格式：YYYY-MM-DD HH:MM:SS', '2026-07-20 12:00:00'],
        };
        function refresh() {
            const mode = modeInputs.find(input => input.checked)?.value || 'filter';
            modeRows.forEach(row => row.hidden = row.dataset.modeRow !== mode);
            typeRow.hidden = mode !== 'filter' || scope.value !== 'card_type';
            [...typeSelect.options].forEach(option => {
                option.hidden = !!application.value && !!option.dataset.application && option.dataset.application !== application.value;
            });
            if (typeSelect.selectedOptions[0]?.hidden) typeSelect.value = '';
            const config = valueConfig[action.value];
            valueRow.hidden = !config;
            valueInput.required = !!config;
            valueInput.placeholder = config?.[1] || '';
            valueHelp.textContent = config?.[0] || '';
            const selection = mode === 'ids' ? '按索引 ID 处理' : `${application.selectedOptions[0].text} / ${scope.selectedOptions[0].text}`;
            summary.textContent = `模式：${selection}；功能：${action.selectedOptions[0].text}${config && valueInput.value ? `；N=${valueInput.value}` : ''}`;
        }
        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
        refresh();
    })();
    </script>
@endsection
