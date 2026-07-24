@extends('admin.layout')

@section('title', '后台管理员')

@section('content')
@php($canUpdateAdministrators = \Illuminate\Support\Facades\Gate::allows('administrators.update'))
<x-admin.table-shell title="系统管理员" subtitle="管理后台登录账号与角色权限" panel-class="administrator-list-panel">
    <x-slot:headingActions>
        @can('administrators.create')<a class="button button-primary" href="{{ route('admin.administrators.create', ['adminPath' => $adminPath]) }}">添加管理员</a>@endcan
    </x-slot:headingActions>
    <x-slot:search><x-admin.filter-bar>
        <x-slot:field><select aria-label="搜索字段"><option>管理员账号</option></select></x-slot:field>
        <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="搜索内容"></x-slot:keyword>
        <x-slot:sort><select name="sort"><option value="oldest" @selected(request('sort')==='oldest')>正序</option><option value="latest" @selected(request('sort','latest')==='latest')>倒序</option></select></x-slot:sort>
    </x-admin.filter-bar></x-slot:search>
    <x-slot:toolbar>
        <div class="admin-table-toolbar-summary"><strong>管理员列表</strong><span>共 {{ $administrators->total() }} 条数据</span></div>
        <div class="admin-table-utility-tools">
            <button type="button" data-table-density-toggle aria-label="切换表格密度" title="切换表格密度"><span aria-hidden="true">▥</span></button>
            <button type="button" data-table-export aria-label="导出当前表格" title="导出当前表格"><span aria-hidden="true">↥</span></button>
            <button type="button" onclick="window.print()" aria-label="打印" title="打印"><span aria-hidden="true">▤</span></button>
        </div>
    </x-slot:toolbar>
        <table>
            <thead><tr><th>管理员账号</th><th>账号状态</th><th>角色/权限</th><th>二级密码</th><th>最后活动时间</th><th>操作</th></tr></thead>
            <tbody>
            @forelse($administrators as $managed)
                @php($isSystemAdministrator = $managed->role === 'super_admin' || $managed->roles->contains('code','super_admin'))
                <tr>
                    <td><strong>{{ $managed->username }}</strong><small>#{{ $managed->id }}{{ $isSystemAdministrator ? ' · 系统管理员' : '' }}</small></td>
                    <td><span class="status-tag status-{{ $managed->status }}">{{ $managed->status === 'active' ? '正常' : '冻结' }}</span></td>
                    <td>{{ $managed->roles->pluck('name')->join('、') ?: $managed->role }}</td>
                    <td>{{ $managed->secondary_password_required ? '已启用' : '关闭' }}</td>
                    <td>{{ $managed->last_login_at?->diffForHumans() ?? '—' }}</td>
                    <td><button class="administrator-manage-button" type="button" data-admin-manage
                        data-title="{{ $managed->username }}" data-role="{{ $managed->roles->first()?->code ?? $managed->role }}" data-status="{{ $managed->status }}"
                        data-protected="{{ $isSystemAdministrator || $managed->is($administrator) || !$canUpdateAdministrators ? '1' : '0' }}"
                        data-update-url="{{ route('admin.administrators.update',['adminPath'=>$adminPath,'managedAdministrator'=>$managed]) }}"
                        data-delete-url="{{ route('admin.administrators.destroy',['adminPath'=>$adminPath,'managedAdministrator'=>$managed]) }}">
                        {{ $isSystemAdministrator || $managed->is($administrator) || !$canUpdateAdministrators ? '查看' : '操作' }}
                    </button></td>
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
                <label><span>角色权限</span><select name="role_code" data-admin-role>@foreach($roles as $role)<option value="{{ $role->code }}">{{ $role->name }}</option>@endforeach</select></label>
                <label><span>账号状态</span><select name="status" data-admin-status><option value="active">启用</option><option value="frozen">冻结</option></select></label>
                <div><label><span>新登录密码（可选）</span><input type="password" name="password" minlength="6"></label><label><span>确认新密码</span><input type="password" name="password_confirmation" minlength="6"></label></div>
                <p data-admin-protected-note hidden>该账号是系统管理员或当前登录账号，仅供查看。</p>
            </div>
            <footer><button type="button" data-admin-manage-close>取消</button><button class="button button-primary" type="submit" data-admin-save>保存修改</button></footer>
        </form>
        @can('administrators.delete')<form method="post" data-admin-delete-form onsubmit="return confirm('确定删除该后台管理员吗？')">@csrf @method('DELETE')<button class="administrator-dialog-delete" type="submit" data-admin-delete>删除账号</button></form>@endcan
</x-admin.window-dialog>
<script>(()=>{const d=document.querySelector('[data-admin-manage-dialog]');if(!d)return;const update=d.querySelector('[data-admin-update-form]'),del=d.querySelector('[data-admin-delete-form]'),role=d.querySelector('[data-admin-role]'),status=d.querySelector('[data-admin-status]'),save=d.querySelector('[data-admin-save]'),deleteButton=d.querySelector('[data-admin-delete]'),note=d.querySelector('[data-admin-protected-note]');document.querySelectorAll('[data-admin-manage]').forEach(button=>button.addEventListener('click',()=>{const locked=button.dataset.protected==='1';d.querySelector('[data-admin-manage-title]').textContent=button.dataset.title;update.action=button.dataset.updateUrl;if(del)del.action=button.dataset.deleteUrl;role.value=button.dataset.role;status.value=button.dataset.status;[role,status,...update.querySelectorAll('input[type=password]')].forEach(field=>field.disabled=locked);save.hidden=locked;if(deleteButton)deleteButton.hidden=locked;note.hidden=!locked;d.dispatchEvent(new CustomEvent('admin-window:open',{bubbles:true}))}));d.querySelectorAll('[data-admin-manage-close]').forEach(b=>b.addEventListener('click',()=>d.close()));d.addEventListener('click',e=>{if(e.target===d&&d.dataset.windowScope!=='workspace')d.close()})})();</script>

@endsection
