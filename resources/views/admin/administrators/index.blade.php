@extends('admin.layout')

@section('title', '后台管理员')

@section('content')
@php($canUpdateAdministrators = \Illuminate\Support\Facades\Gate::allows('administrators.update'))
<x-admin.table-shell title="系统管理员" subtitle="管理后台登录账号与角色权限" panel-class="administrator-list-panel">
    <x-slot:headingActions>
        <a class="button button-secondary" href="{{ route('admin.administrators.security', ['adminPath' => $adminPath]) }}">个人安全设置</a>
        @can('administrators.create')<a class="button button-primary" href="{{ route('admin.administrators.create', ['adminPath' => $adminPath]) }}">添加管理员</a>@endcan
    </x-slot:headingActions>
    <x-slot:search><x-admin.filter-bar>
        <x-slot:field><select aria-label="搜索字段"><option>管理员账号</option></select></x-slot:field>
        <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="搜索内容"></x-slot:keyword>
        <x-slot:sort><select name="sort"><option value="oldest" @selected(request('sort')==='oldest')>正序</option><option value="latest" @selected(request('sort','latest')==='latest')>倒序</option></select></x-slot:sort>
    </x-admin.filter-bar></x-slot:search>
    <x-slot:toolbar>
        <div class="admin-table-toolbar-summary"><strong>管理员列表</strong><span>共 {{ $administrators->total() }} 条数据</span></div>
        <x-admin.table-tools />
    </x-slot:toolbar>
        <table>
            <thead><tr><th>管理员账号</th><th>账号状态</th><th>角色/权限</th><th>二级密码</th><th>最后活动时间</th><th class="admin-action-column">操作</th></tr></thead>
            <tbody>
            @forelse($administrators as $managed)
                @php($isSystemAdministrator = $managed->role === 'super_admin' || $managed->roles->contains('code','super_admin'))
                <tr>
                    <td><strong>{{ $managed->username }}</strong><small>#{{ $managed->id }}{{ $isSystemAdministrator ? ' · 系统管理员' : '' }}</small></td>
                    <td><span class="status-tag status-{{ $managed->status }}">{{ $managed->status === 'active' ? '正常' : '冻结' }}</span></td>
                    <td>{{ $managed->roles->pluck('name')->join('、') ?: $managed->role }}</td>
                    <td>{{ $managed->secondary_password_required ? '已启用' : '关闭' }}</td>
                    <td>{{ $managed->last_login_at?->diffForHumans() ?? '—' }}</td>
                    <td class="admin-action-column"><button class="administrator-manage-button admin-table-action admin-table-action--primary" type="button" data-admin-manage
                        data-title="{{ $managed->username }}" data-role="{{ $managed->roles->first()?->code ?? $managed->role }}" data-status="{{ $managed->status }}"
                        data-protected="{{ $isSystemAdministrator || $managed->is($administrator) || !$canUpdateAdministrators ? '1' : '0' }}"
                        data-update-url="{{ route('admin.administrators.update',['adminPath'=>$adminPath,'managedAdministrator'=>$managed]) }}"
                        data-delete-url="{{ route('admin.administrators.destroy',['adminPath'=>$adminPath,'managedAdministrator'=>$managed]) }}">
                        {{ $isSystemAdministrator || $managed->is($administrator) || !$canUpdateAdministrators ? '查看' : '操作' }}
                    </button>
                    @if($managed->is($administrator))<a class="admin-table-action" href="{{ route('admin.administrators.security', ['adminPath' => $adminPath]) }}">修改账号</a>@endif
                    </td>
                </tr>
            @empty<tr><td colspan="6" class="empty-cell">暂无管理员数据。</td></tr>@endforelse
            </tbody>
        </table>
    @if($administrators->hasPages())
        <x-slot:footer><div class="pager">{{ $administrators->links() }}</div></x-slot:footer>
    @endif
</x-admin.table-shell>

<x-admin.window-dialog class="administrator-manage-dialog" data-admin-manage-dialog>
    <x-slot:heading><small>ADMINISTRATOR</small><h3 data-admin-manage-title>管理员操作</h3></x-slot:heading>
        <form method="post" data-admin-update-form>@csrf @method('PUT')
            <div class="administrator-dialog-fields">
                <label><span>管理员用户名</span><input name="username" data-admin-username required minlength="3" maxlength="32" pattern="[A-Za-z][A-Za-z0-9_-]+" autocomplete="username"></label>
                <label><span>角色权限</span><select name="role_code" data-admin-role>@foreach($roles as $role)<option value="{{ $role->code }}">{{ $role->name }}</option>@endforeach</select></label>
                <label><span>账号状态</span><select name="status" data-admin-status><option value="active">启用</option><option value="frozen">冻结</option></select></label>
                <div><label><span>新登录密码（可选）</span><input type="password" name="password" minlength="6" maxlength="128" autocomplete="new-password"></label><label><span>确认新密码</span><input type="password" name="password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"></label></div>
                <div><label><span>新二级密码（留空保持原设置）</span><input type="password" name="secondary_password" minlength="6" maxlength="128" autocomplete="new-password"></label><label><span>确认新二级密码</span><input type="password" name="secondary_password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"></label></div>
                <p>设置二级密码后，登录时须同时验证两个不同密码。修改账号信息后旧登录会话失效。</p>
                <p data-admin-protected-note hidden>该账号受保护；当前账号请使用上方“个人安全设置”。</p>
            </div>
            <footer><button type="button" data-admin-manage-close>取消</button><button class="button button-primary" type="submit" data-admin-save>保存修改</button></footer>
        </form>
        @can('administrators.delete')<form method="post" data-admin-delete-form onsubmit="return confirm('确定删除该后台管理员吗？')">@csrf @method('DELETE')<button class="administrator-dialog-delete" type="submit" data-admin-delete>删除账号</button></form>@endcan
</x-admin.window-dialog>
<script>(()=>{const d=document.querySelector('[data-admin-manage-dialog]');if(!d)return;const update=d.querySelector('[data-admin-update-form]'),del=d.querySelector('[data-admin-delete-form]'),role=d.querySelector('[data-admin-role]'),status=d.querySelector('[data-admin-status]'),save=d.querySelector('[data-admin-save]'),deleteButton=d.querySelector('[data-admin-delete]'),note=d.querySelector('[data-admin-protected-note]');document.querySelectorAll('[data-admin-manage]').forEach(button=>button.addEventListener('click',()=>{update.reset();const locked=button.dataset.protected==='1';const username=update.querySelector('[data-admin-username]');username.value=button.dataset.title;d.querySelector('[data-admin-manage-title]').textContent=button.dataset.title;update.action=button.dataset.updateUrl;if(del)del.action=button.dataset.deleteUrl;role.value=button.dataset.role;status.value=button.dataset.status;[username,role,status,...update.querySelectorAll('input[type=password]')].forEach(field=>field.disabled=locked);save.hidden=locked;if(deleteButton)deleteButton.hidden=locked;note.hidden=!locked;d.dispatchEvent(new CustomEvent('admin-window:open',{bubbles:true}))}));d.querySelectorAll('[data-admin-manage-close]').forEach(b=>b.addEventListener('click',()=>d.close()));d.addEventListener('click',e=>{if(e.target===d&&d.dataset.windowScope!=='workspace')d.close()})})();</script>

@endsection
