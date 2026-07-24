@extends('admin.layout')

@section('title', '代理账号管理')

@section('content')
@php($canManageAgents = \Illuminate\Support\Facades\Gate::allows('users.manage'))
<x-admin.table-shell title="代理账号管理" subtitle="管理代理后台账号及登录状态" panel-class="administrator-list-panel agent-list-panel">
    <x-slot:headingActions>
        @can('users.create')
            <a class="button button-primary" href="{{ route('admin.agents.create', ['adminPath' => $adminPath]) }}">添加代理账号</a>
        @endcan
    </x-slot:headingActions>

    <x-slot:search>
        <x-admin.filter-bar>
            <x-slot:field><select aria-label="搜索字段"><option>代理账号</option></select></x-slot:field>
            <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="请输入代理账号"></x-slot:keyword>
            <x-slot:sort><select name="sort">
                <option value="oldest" @selected(request('sort') === 'oldest')>正序</option>
                <option value="latest" @selected(request('sort', 'latest') === 'latest')>倒序</option>
            </select></x-slot:sort>
        </x-admin.filter-bar>
    </x-slot:search>

    <x-slot:toolbar>
        <div class="admin-table-toolbar-summary"><strong>代理账号列表</strong><span>共 {{ $agents->total() }} 条数据</span></div>
        <div class="admin-table-utility-tools">
            <button type="button" data-table-density-toggle aria-label="切换表格密度" title="切换表格密度"><span aria-hidden="true">▥</span></button>
            <button type="button" data-table-export aria-label="导出当前表格" title="导出当前表格"><span aria-hidden="true">↥</span></button>
            <button type="button" onclick="window.print()" aria-label="打印" title="打印"><span aria-hidden="true">▤</span></button>
        </div>
    </x-slot:toolbar>

    <table>
        <thead><tr><th>代理账号</th><th>账号状态</th><th>购卡折扣</th><th>账户余额</th><th>最后活动时间</th><th>操作</th></tr></thead>
        <tbody>
        @forelse($agents as $agent)
            <tr>
                <td><strong>{{ $agent->username }}</strong><small>#{{ $agent->id }}</small></td>
                <td><span class="status-tag status-{{ $agent->status }}">{{ $agent->status === 'active' ? '正常' : '冻结' }}</span></td>
                <td>{{ number_format(($agent->agent_discount_basis_points ?: 10000) / 1000, 1) }} 折</td>
                <td>¥ {{ number_format($agent->agent_balance_cents / 100, 2) }}</td>
                <td>{{ $agent->last_login_at?->diffForHumans() ?? '—' }}</td>
                <td>
                    @if($canManageAgents)
                        <button class="administrator-manage-button" type="button" data-agent-manage
                                data-title="{{ $agent->username }}" data-status="{{ $agent->status }}"
                                data-balance="{{ number_format($agent->agent_balance_cents / 100, 2, '.', '') }}"
                                data-discount="{{ number_format(($agent->agent_discount_basis_points ?: 10000) / 1000, 1, '.', '') }}"
                                data-note="{{ $agent->agent_note }}"
                                data-contact-qq="{{ $agent->contact_qq }}"
                                data-contact-email="{{ $agent->contact_email }}"
                                data-contact-phone="{{ $agent->contact_phone }}"
                                data-all-applications="{{ $agent->agent_all_applications ? '1' : '0' }}"
                                data-applications="{{ $agent->agentApplications->pluck('id')->implode(',') }}"
                                data-card-types="{{ $agent->agentCardTypes->pluck('id')->implode(',') }}"
                                data-created-at="{{ $agent->created_at?->format('Y-m-d H:i:s') ?? '—' }}"
                                data-last-login-at="{{ $agent->last_login_at?->format('Y-m-d H:i:s') ?? '暂无登录记录' }}"
                                data-login-ip-recorded="{{ filled($agent->last_login_ip_hash) ? '已安全记录' : '暂无记录' }}"
                                data-update-url="{{ route('admin.agents.update', ['adminPath' => $adminPath, 'agent' => $agent]) }}"
                                data-delete-url="{{ route('admin.agents.destroy', ['adminPath' => $adminPath, 'agent' => $agent]) }}">操作</button>
                    @else
                        <span class="protected-admin-badge">仅查看</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty-cell">暂无代理账号。</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($agents->hasPages())
        <x-slot:footer><div class="pager">{{ $agents->links() }}</div></x-slot:footer>
    @endif
</x-admin.table-shell>

@can('users.manage')
<x-admin.window-dialog class="administrator-manage-dialog agent-manage-dialog" data-agent-manage-dialog>
        <x-slot:heading><small>AGENT ACCOUNT</small><h3 data-agent-manage-title>代理账号操作</h3></x-slot:heading>
        <form method="post" data-agent-update-form autocomplete="off">
            @csrf @method('PUT')
            <div class="administrator-dialog-fields">
                <div class="agent-dialog-grid">
                    <label><span>代理账号</span><input name="username" minlength="3" maxlength="32" required data-agent-username autocomplete="off"><small>字母开头，可使用字母、数字、下划线和短横线</small></label>
                    <label><span>账号状态</span><select name="status" data-agent-status><option value="active">启用</option><option value="frozen">冻结</option></select></label>
                    <label><span>账户余额</span><input type="number" name="balance" min="0" max="9999999" step="0.01" data-agent-balance></label>
                    <label><span>购卡折扣</span><input type="number" name="purchase_discount" min="0" max="10" step="0.1" data-agent-discount><small>填 8.8 表示八八折；填 0 使用标准价</small></label>
                    <label><span>联系 QQ</span><input name="contact_qq" maxlength="32" data-agent-contact-qq placeholder="选填" autocomplete="off"></label>
                    <label><span>联系邮箱</span><input type="email" name="contact_email" maxlength="255" data-agent-contact-email placeholder="选填" autocomplete="off"></label>
                    <label><span>联系电话</span><input name="contact_phone" maxlength="32" data-agent-contact-phone placeholder="选填" autocomplete="off"></label>
                </div>
                <label><span>后台备注</span><textarea name="agent_note" maxlength="500" rows="3" data-agent-note placeholder="仅后台管理员可见"></textarea></label>
                <div class="agent-dialog-grid">
                    <label><span>新登录密码（选填）</span><input type="password" name="password" minlength="6" autocomplete="new-password"></label>
                    <label><span>确认新密码</span><input type="password" name="password_confirmation" minlength="6" autocomplete="new-password"></label>
                </div>
                <fieldset class="agent-application-access">
                    <legend>可用软件分配</legend>
                    <input type="hidden" name="all_applications" value="0">
                    <label class="agent-all-applications"><input type="checkbox" name="all_applications" value="1" data-agent-all-applications> 默认全部软件</label>
                    <div data-agent-application-options class="agent-application-groups">
                        @forelse($applications as $application)
                            <section data-agent-application-group>
                                <label class="agent-application-parent"><input type="checkbox" name="application_ids[]" value="{{ $application->id }}" data-agent-application-toggle> <span>{{ $application->name }}</span><small>{{ $application->code }}</small></label>
                                <div class="agent-card-type-options">
                                    @forelse($application->cardTypes as $cardType)
                                        <label><input type="checkbox" name="card_type_ids[]" value="{{ $cardType->id }}" data-agent-card-type> <span>{{ $cardType->name }}</span><small>{{ $cardType->code }}</small></label>
                                    @empty
                                        <small>该软件暂无可用充值卡类型</small>
                                    @endforelse
                                </div>
                            </section>
                        @empty
                            <small>当前还没有软件，保存后新建的软件将自动对该代理开放。</small>
                        @endforelse
                    </div>
                    <p>关闭“默认全部软件”后，请先选软件，再勾选允许代理制作的天卡、周卡、月卡等充值卡类型。</p>
                </fieldset>
                <dl class="agent-account-metadata">
                    <div><dt>注册时间</dt><dd data-agent-created-at>—</dd></div>
                    <div><dt>最后登录</dt><dd data-agent-last-login-at>—</dd></div>
                    <div><dt>登录 IP</dt><dd data-agent-login-ip-recorded>—</dd></div>
                </dl>
            </div>
            <footer><button type="button" data-agent-manage-close>取消</button><button class="button button-primary" type="submit">保存修改</button></footer>
        </form>
        <form method="post" data-agent-delete-form onsubmit="return confirm('确定删除该代理账号吗？')">
            @csrf @method('DELETE')
            <button class="administrator-dialog-delete" type="submit">删除账号</button>
        </form>
</x-admin.window-dialog>
<script>(()=>{const d=document.querySelector('[data-agent-manage-dialog]');if(!d)return;const update=d.querySelector('[data-agent-update-form]'),del=d.querySelector('[data-agent-delete-form]'),username=d.querySelector('[data-agent-username]'),status=d.querySelector('[data-agent-status]'),balance=d.querySelector('[data-agent-balance]'),discount=d.querySelector('[data-agent-discount]'),note=d.querySelector('[data-agent-note]'),qq=d.querySelector('[data-agent-contact-qq]'),email=d.querySelector('[data-agent-contact-email]'),phone=d.querySelector('[data-agent-contact-phone]'),all=d.querySelector('[data-agent-all-applications]'),groups=[...d.querySelectorAll('[data-agent-application-group]')];const syncAccess=()=>groups.forEach(group=>{const app=group.querySelector('[data-agent-application-toggle]'),types=[...group.querySelectorAll('[data-agent-card-type]')];app.disabled=all.checked;app.closest('label')?.classList.toggle('is-disabled',all.checked);types.forEach(input=>{input.disabled=all.checked||!app.checked;input.closest('label')?.classList.toggle('is-disabled',input.disabled)})});all.addEventListener('change',syncAccess);groups.forEach(group=>{const app=group.querySelector('[data-agent-application-toggle]'),types=[...group.querySelectorAll('[data-agent-card-type]')];app.addEventListener('change',()=>{types.forEach(input=>input.checked=app.checked);syncAccess()});types.forEach(input=>input.addEventListener('change',()=>{if(input.checked)app.checked=true;syncAccess()}))});document.querySelectorAll('[data-agent-manage]').forEach(button=>button.addEventListener('click',()=>{update.reset();d.querySelector('[data-agent-manage-title]').textContent=button.dataset.title;update.action=button.dataset.updateUrl;del.action=button.dataset.deleteUrl;username.value=button.dataset.title;status.value=button.dataset.status;balance.value=button.dataset.balance;discount.value=button.dataset.discount;note.value=button.dataset.note||'';qq.value=button.dataset.contactQq||'';email.value=button.dataset.contactEmail||'';phone.value=button.dataset.contactPhone||'';all.checked=button.dataset.allApplications==='1';const selectedApps=new Set((button.dataset.applications||'').split(',').filter(Boolean)),selectedTypes=new Set((button.dataset.cardTypes||'').split(',').filter(Boolean));groups.forEach(group=>{const app=group.querySelector('[data-agent-application-toggle]');app.checked=selectedApps.has(app.value);group.querySelectorAll('[data-agent-card-type]').forEach(input=>input.checked=selectedTypes.has(input.value))});d.querySelector('[data-agent-created-at]').textContent=button.dataset.createdAt;d.querySelector('[data-agent-last-login-at]').textContent=button.dataset.lastLoginAt;d.querySelector('[data-agent-login-ip-recorded]').textContent=button.dataset.loginIpRecorded;syncAccess();d.dispatchEvent(new CustomEvent('admin-window:open',{bubbles:true}))}));d.querySelectorAll('[data-agent-manage-close]').forEach(button=>button.addEventListener('click',()=>d.close()));d.addEventListener('click',event=>{if(event.target===d&&d.dataset.windowScope!=='workspace')d.close()})})();</script>
@endcan
@endsection
