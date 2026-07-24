@extends('admin.layout')

@section('title', '软件用户批量维护')

@section('content')
<section class="dashboard-panel card-maintenance-panel user-maintenance-panel">
    <header><div><h2>应用账号批量维护</h2></div></header>
    <form method="post" action="{{ route('admin.application-users.maintenance.run', ['adminPath' => $adminPath]) }}" data-user-maintenance-form>
        @csrf
        <div class="card-maintenance-rows">
            <div class="card-maintenance-mode">
                <label><input type="radio" name="selection_mode" value="ids" @checked(old('selection_mode') === 'ids')><b>A.ID批量处理</b><span>通过索引ID维护</span></label>
                <label><input type="radio" name="selection_mode" value="filter" @checked(old('selection_mode', 'filter') === 'filter')><b>B.筛选选处理</b><span>通过范围选择维护</span></label>
            </div>

            <div class="card-type-form-row" data-mode-row="ids" hidden>
                <strong>索引 ID</strong><input name="id_list" value="{{ old('id_list') }}" placeholder="例如：1,2,8-20"><small>支持逗号、空格和连续范围。</small>
            </div>
            <div class="card-type-form-row">
                <strong>选择软件</strong>
                <select name="application_id" required>
                    <option value="">请选择软件</option>
                    @foreach($applications as $application)<option value="{{ $application->id }}" @selected((string)old('application_id') === (string)$application->id)>{{ $application->name }}（{{ $application->code }}）</option>@endforeach
                </select><small>选择要维护的软件</small>
            </div>
            <div class="card-type-form-row" data-mode-row="filter">
                <strong>维护范围</strong>
                <select name="scope" data-user-scope>
                    <option value="all">全部用户</option><option value="not_expired" @selected(old('scope')==='not_expired')>没到期用（时间模式判断）</option>
                    <option value="expired" @selected(old('scope')==='expired')>已经到期（时间模式判断）</option><option value="expires_within" @selected(old('scope')==='expires_within')>X天前到期用户*</option>
                    <option value="uid" @selected(old('scope')==='uid')>用户UID[逻辑]</option><option value="binding" @selected(old('scope')==='binding')>绑定特征[逻辑]</option>
                    <option value="expiry" @selected(old('scope')==='expiry')>到期时间[逻辑] (时间截/点)</option><option value="added_time" @selected(old('scope')==='added_time')>添加时间[逻辑]</option>
                </select><small>维护类型选择</small>
            </div>
            <div class="card-type-form-row" data-scope-value-row hidden>
                <strong>范围值</strong><input name="scope_value" value="{{ old('scope_value') }}" data-scope-value><small data-scope-help></small>
            </div>

            <div class="maintenance-action-row">
                <strong><span>选择维护功能</span></strong>
                <div class="maintenance-action-options" data-user-actions>
                    @foreach(['add_time'=>'添加时间N(秒/点)','subtract_time'=>'减少时间N(秒/点)','freeze'=>'冻结账号','unfreeze'=>'解除冻结','set_expiry'=>'到期时间N(点/时间截)','bind_feature'=>'绑定特征N','delete'=>'删除(谨慎操作)' ] as $value => $label)
                        <label><input type="radio" name="action" value="{{ $value }}" @checked(old('action')===$value) required><span>{{ $label }}</span></label>
                    @endforeach
                </div><small>选择要执行的结果功能</small>
            </div>
            <div class="card-type-form-row" data-action-value-row hidden>
                <strong>维护值 N</strong><input name="action_value" value="{{ old('action_value') }}" data-action-value><small data-action-help></small>
            </div>
            <div class="card-maintenance-warning"><strong>注意</strong><span>在批量操作数据库时请先备份相关数据库，数据无价。</span></div>
            <label class="card-maintenance-confirm"><input type="checkbox" name="confirmed" value="1" required @checked(old('confirmed'))><span>我已核对处理范围和维护功能</span></label>
        </div>
        <footer class="card-maintenance-footer"><div><strong>操作摘要</strong><span data-user-summary></span></div><button class="button button-primary" type="submit">确认操作</button></footer>
    </form>
</section>
<script>
(() => {
 const form=document.querySelector('[data-user-maintenance-form]'); if(!form)return;
 const modes=[...form.querySelectorAll('[name=selection_mode]')], rows=[...form.querySelectorAll('[data-mode-row]')], app=form.querySelector('[name=application_id]'), scope=form.querySelector('[data-user-scope]'), scopeRow=form.querySelector('[data-scope-value-row]'), scopeInput=form.querySelector('[data-scope-value]'), scopeHelp=form.querySelector('[data-scope-help]'), valueRow=form.querySelector('[data-action-value-row]'), valueInput=form.querySelector('[data-action-value]'), actionHelp=form.querySelector('[data-action-help]'), summary=form.querySelector('[data-user-summary]');
 const scopeHints={expires_within:'填写天数，例如 7',uid:'填写 UID 或用户名关键字',binding:'填写绑定特征关键字',expiry:'填写日期时间或 Unix 时间戳',added_time:'填写用户创建日期'};
 const actionHints={add_time:'填写增加的秒数',subtract_time:'填写减少的秒数',set_expiry:'填写日期时间或 Unix 时间戳',bind_feature:'填写新的绑定特征'};
 function refresh(){const mode=modes.find(x=>x.checked)?.value||'filter'; rows.forEach(r=>r.hidden=r.dataset.modeRow!==mode); const sh=mode==='filter'?scopeHints[scope.value]:null; scopeRow.hidden=!sh; scopeInput.required=!!sh; scopeHelp.textContent=sh||''; const a=form.querySelector('[name=action]:checked'); const ah=a&&actionHints[a.value]; valueRow.hidden=!ah; valueInput.required=!!ah; actionHelp.textContent=ah||''; summary.textContent=`模式：${mode==='ids'?'按索引 ID 维护':'通过范围筛选维护'}；功能：${a?a.nextElementSibling.textContent:'未选择维护功能'}；软件：${app.selectedOptions[0].text}；范围：${scope.selectedOptions[0].text}`;}
 form.addEventListener('input',refresh); form.addEventListener('change',refresh); refresh();
})();
</script>
@endsection
