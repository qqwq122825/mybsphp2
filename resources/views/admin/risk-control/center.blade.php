@extends('admin.layout')

@section('title', '封控中心')

@section('content')
<form id="risk-center-release-form" method="post" action="{{ route('admin.risk-control.center.release', ['adminPath' => $adminPath]) }}">
    @csrf
</form>

<x-admin.table-shell title="封控中心" subtitle="近 24 小时 {{ $metrics['events_24h'] }} 次 · 设备 {{ $metrics['blocked_devices'] }} · IP {{ $metrics['blocked_ips'] }}。拉黑后任意软件登录显示卡密不存在。" panel-class="bsphp-log-panel">
    <x-slot:headingActions>
        <button class="button button-primary" type="button" data-window-open="add-risk-block">添加封禁</button>
    </x-slot:headingActions>
    <x-slot:search>
        <x-admin.filter-bar :action="route('admin.risk-control.center', ['adminPath' => $adminPath])" button-label="⌕" :icon-only="true">
            <x-slot:field>
                <select name="subject_type" aria-label="搜索字段">
                    <option value="all" @selected(request('subject_type', 'all') === 'all')>全部</option>
                    <option value="device" @selected(request('subject_type') === 'device')>设备</option>
                    <option value="ip" @selected(request('subject_type') === 'ip')>IP</option>
                </select>
            </x-slot:field>
            <x-slot:keyword>
                <input name="keyword" value="{{ request('keyword') }}" placeholder="序列号或 IP">
            </x-slot:keyword>
            <x-slot:sort>
                <select name="sort">
                    <option value="latest" @selected(request('sort', 'latest') === 'latest')>正序</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>倒序</option>
                </select>
            </x-slot:sort>
        </x-admin.filter-bar>
    </x-slot:search>
    <x-slot:toolbar>
        <div>
            <button type="submit" form="risk-center-release-form" onclick="return confirm('确定解除选中的封控吗？')">解除选定</button>
        </div>
        <div class="admin-table-utility-tools">
            <button type="button" data-table-density-toggle aria-label="切换表格密度" title="切换表格密度"><span aria-hidden="true">▥</span></button>
            <button type="button" data-table-export aria-label="导出当前表格" title="导出当前表格"><span aria-hidden="true">↥</span></button>
            <button type="button" onclick="window.print()" aria-label="打印" title="打印"><span aria-hidden="true">▤</span></button>
        </div>
    </x-slot:toolbar>
    <table>
        <thead>
            <tr>
                <th class="admin-select-column"><input type="checkbox" aria-label="全选" data-card-select-all></th>
                <th>类型</th>
                <th>封控对象</th>
                <th>关联</th>
                <th>原因</th>
                <th>时间</th>
                <th>处置</th>
            </tr>
        </thead>
        <tbody>
        @forelse($blocks as $block)
            <tr>
                <td class="admin-select-column">
                    <input type="checkbox" name="block_keys[]" value="{{ $block['key'] }}" form="risk-center-release-form" aria-label="选择封控 {{ $block['value'] }}" data-card-select>
                </td>
                <td>{{ $block['kind'] === 'device' ? '设备' : 'IP' }}</td>
                <td><strong><code>{{ $block['value'] }}</code></strong></td>
                <td>{{ $block['related'] ?: '—' }}</td>
                <td>{{ $block['reason'] }}</td>
                <td>{{ $block['at']?->format('Y-m-d H:i:s') ?: '—' }}</td>
                <td>
                    <button type="submit" form="risk-center-release-form" name="block_keys[]" value="{{ $block['key'] }}" onclick="return confirm('确定解除该封控吗？')">解除</button>
                </td>
            </tr>
        @empty
            <tr><td class="empty-cell" colspan="7">当前没有封控记录。</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($blocks->hasPages())
        <x-slot:footer>
            <div class="pager">{{ $blocks->links() }}</div>
        </x-slot:footer>
    @endif
</x-admin.table-shell>

<x-admin.window-dialog id="add-risk-block" title="添加封禁" eyebrow="RISK BLOCK" :workspace="false" :maximized="false"
                       :auto-open="$errors->has('device_serial') || $errors->has('ip')"
                       class="risk-block-dialog">
    <form method="post" action="{{ route('admin.risk-control.global-blocks.store', ['adminPath' => $adminPath]) }}">
        @csrf
        <div class="administrator-create-rows">
            <p class="administrator-create-note">可只填设备、只填 IP，或两个都填。拉黑后任意软件登录都显示卡密不存在。</p>
            <label class="administrator-create-row">
                <strong>设备序列号</strong>
                <input name="device_serial" value="{{ old('device_serial') }}" placeholder="例如 D2QDT96KLH" maxlength="160">
                <small>{{ $errors->first('device_serial') ?: '按特征码拉黑' }}</small>
            </label>
            <label class="administrator-create-row">
                <strong>IP 地址</strong>
                <input name="ip" value="{{ old('ip') }}" placeholder="例如 203.0.113.88">
                <small>{{ $errors->first('ip') ?: '按登录 IP 拉黑' }}</small>
            </label>
            <label class="administrator-create-row">
                <strong>备注</strong>
                <input name="note" value="{{ old('note') }}" placeholder="可选" maxlength="500">
                <small>仅后台可见</small>
            </label>
            <div class="administrator-create-actions">
                <button class="button button-primary" type="submit" onclick="return confirm('确定拉黑所填设备或 IP 吗？')">确认拉黑</button>
                <button class="button button-secondary" type="button" data-window-action="close">取消</button>
            </div>
        </div>
    </form>
</x-admin.window-dialog>
@endsection
