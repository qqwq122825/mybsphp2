@extends('admin.layout')

@section('title', $application->name.' · 软件用户管理')

@section('content')
    @php
        $formatRemainingValidity = static function ($expiresAt): string {
            if (!$expiresAt) return '永久';
            $remaining = $expiresAt->getTimestamp() - now()->getTimestamp();
            if ($remaining <= 0) return '已到期';
            $days = intdiv($remaining, 86400);
            $hours = intdiv($remaining % 86400, 3600);
            $minutes = intdiv($remaining % 3600, 60);
            $parts = [];
            if ($days > 0) $parts[] = $days.'天';
            if ($hours > 0) $parts[] = $hours.'小时';
            if ($minutes > 0) $parts[] = $minutes.'分';
            return $parts === [] ? '不足1分钟' : implode('', $parts);
        };
    @endphp
    <section class="dashboard-panel bsphp-user-panel">
        <header class="bsphp-card-list-header">
            <h2>软件用户管理 - {{ $application->name }}</h2>
            <span class="bsphp-mode-chip">{{ $userType === 'card' ? '卡串验证模式' : '账号登录模式' }}</span>
        </header>

        <x-admin.filter-bar button-label="⌕" :icon-only="true"
            :action="route('admin.applications.users.index', ['adminPath' => $adminPath, 'application' => $application])">
            <x-slot:field><select aria-label="搜索字段"><option>{{ $userType === 'card' ? '充值卡号(激活码)' : '用户名' }}</option></select></x-slot:field>
            <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="请输入"></x-slot:keyword>
            <x-slot:sort><select name="sort">
                <option value="latest" @selected(request('sort', 'latest') === 'latest')>排序倒序</option>
                <option value="oldest" @selected(request('sort') === 'oldest')>排序正序</option>
            </select></x-slot:sort>
        </x-admin.filter-bar>

        <form class="bsphp-card-table-wrap bsphp-user-table-wrap uses-admin-data-table" method="post"
              action="{{ route('admin.application-users.maintenance.run', ['adminPath' => $adminPath]) }}">
            @csrf
            <input type="hidden" name="selection_mode" value="ids">
            <input type="hidden" name="application_id" value="{{ $application->id }}">
            <input type="hidden" name="scope" value="all">
            <input type="hidden" name="confirmed" value="1">
            <input type="hidden" name="id_list" value="" data-user-bulk-ids>
            <x-admin.table-frame frame-class="software-user-table-component">
                <x-slot:toolbar><div><button type="button" data-user-bulk-open>批量操作</button></div><span>冻结后会阻止继续登录；表格可横向滚动查看全部字段。</span></x-slot:toolbar>
                @if($userType === 'card')
                    <table class="bsphp-card-table bsphp-user-table">
                        <thead><tr>
                            <th class="bsphp-user-select-column"><input type="checkbox" data-card-select-all aria-label="全选"></th>
                            <th class="bsphp-user-identity-column">充值卡号(激活码)</th><th>状态</th><th>账号多开</th><th>机器用量</th>
                            <th class="bsphp-user-expiry-column">到期时间</th><th class="bsphp-user-validity-column">有效期</th><th class="bsphp-user-last-online-column">最后在线</th><th class="bsphp-user-binding-column">绑定特征</th><th>备注</th><th class="bsphp-user-action-column">操作</th>
                        </tr></thead>
                        <tbody>
                        @forelse($users as $card)
                            @php
                                $accountLimit = $card->account_concurrency_limit ?: $card->cardType->account_concurrency_limit;
                                $machineLimit = $card->machine_concurrency_limit ?: $card->cardType->machine_concurrency_limit;
                            @endphp
                            <tr>
                                <td class="bsphp-user-select-column"><input type="checkbox" name="user_ids[]" value="{{ $card->id }}" data-card-select></td>
                                <td class="bsphp-user-identity-column bsphp-user-truncate" title="{{ $card->code_ciphertext }}"><code>{{ $card->code_ciphertext }}</code></td>
                                <td><span class="status-tag status-{{ $card->user_status ?? 'active' }}">{{ ($card->user_status ?? 'active') === 'frozen' ? '冻结' : '正常' }}</span></td>
                                <td>{{ $accountLimit ?: '默认' }}</td><td>{{ $machineLimit ?: '默认' }}</td>
                                <td class="bsphp-user-expiry-column">@if($card->expires_at)<time class="bsphp-user-expiry" datetime="{{ $card->expires_at->toIso8601String() }}" title="{{ $card->expires_at->toIso8601String() }}">{{ $card->expires_at->format('Y-m-d H:i:s') }}</time>@else 永久 @endif</td>
                                <td class="bsphp-user-validity-column"><strong>{{ $formatRemainingValidity($card->expires_at) }}</strong></td>
                                <td class="bsphp-user-last-online-column" title="{{ $card->last_online_at?->format('Y-m-d H:i:s') }}">{{ $card->last_online_at?->diffForHumans() ?? '—' }}</td>
                                <td class="bsphp-binding-value bsphp-user-truncate" title="{{ $card->bound_device_ciphertext ?: '' }}">{{ $card->bound_device_ciphertext ?: '—' }}</td>
                                <td class="bsphp-user-note-column bsphp-user-truncate" title="{{ $card->user_note ?: '' }}">{{ $card->user_note ?: '—' }}</td>
                                <td class="bsphp-user-action-column"><button class="bsphp-detail-button" type="button" data-user-detail
                                    data-title="卡号 {{ $card->code_ciphertext }}"
                                    data-action="{{ route('admin.applications.card-users.update', ['adminPath' => $adminPath, 'application' => $application, 'card' => $card]) }}"
                                    data-expires="{{ $card->expires_at?->format('Y-m-d\TH:i') }}"
                                    data-binding="{{ $card->bound_device_ciphertext }}" data-account-limit="{{ $card->account_concurrency_limit }}"
                                    data-machine-limit="{{ $card->machine_concurrency_limit }}" data-note="{{ $card->user_note }}">详情</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="bsphp-table-empty">无数据</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="bsphp-card-table bsphp-user-table">
                        <thead><tr>
                            <th class="bsphp-user-select-column"><input type="checkbox" data-card-select-all aria-label="全选"></th>
                            <th class="bsphp-user-identity-column">用户名</th><th>状态</th><th>账号多开</th><th>机器用量</th><th class="bsphp-user-expiry-column">到期时间</th>
                            <th class="bsphp-user-validity-column">有效期</th><th class="bsphp-user-last-online-column">最后在线</th><th class="bsphp-user-binding-column">绑定特征</th><th>备注</th><th class="bsphp-user-action-column">操作</th>
                        </tr></thead>
                        <tbody>
                        @forelse($users as $account)
                            <tr>
                                <td class="bsphp-user-select-column"><input type="checkbox" name="user_ids[]" value="{{ $account->id }}" data-card-select></td>
                                <td class="bsphp-user-identity-column bsphp-user-truncate" title="{{ $account->username }}">{{ $account->username }}</td>
                                <td><span class="status-tag status-{{ $account->status }}">{{ $account->status === 'frozen' ? '冻结' : '正常' }}</span></td>
                                <td>{{ $account->account_concurrency_limit ?: '默认' }}</td><td>{{ $account->machine_concurrency_limit ?: '默认' }}</td>
                                <td class="bsphp-user-expiry-column">@if($account->expires_at)<time class="bsphp-user-expiry" datetime="{{ $account->expires_at->toIso8601String() }}" title="{{ $account->expires_at->toIso8601String() }}">{{ $account->expires_at->format('Y-m-d H:i:s') }}</time>@else 永久 @endif</td>
                                <td class="bsphp-user-validity-column"><strong>{{ $formatRemainingValidity($account->expires_at) }}</strong></td>
                                <td class="bsphp-user-last-online-column" title="{{ $account->last_login_at?->format('Y-m-d H:i:s') }}">{{ $account->last_login_at?->diffForHumans() ?? '—' }}</td>
                                <td class="bsphp-binding-value bsphp-user-truncate" title="{{ $account->bound_device_ciphertext ?: '' }}">{{ $account->bound_device_ciphertext ?: '—' }}</td>
                                <td class="bsphp-user-note-column bsphp-user-truncate" title="{{ $account->note ?: '' }}">{{ $account->note ?: '—' }}</td>
                                <td class="bsphp-user-action-column"><button class="bsphp-detail-button" type="button" data-user-detail
                                    data-title="账号 {{ $account->username }}"
                                    data-action="{{ route('admin.applications.account-users.update', ['adminPath' => $adminPath, 'application' => $application, 'account' => $account]) }}"
                                    data-expires="{{ $account->expires_at?->format('Y-m-d\TH:i') }}"
                                    data-binding="{{ $account->bound_device_ciphertext }}" data-account-limit="{{ $account->account_concurrency_limit }}"
                                    data-machine-limit="{{ $account->machine_concurrency_limit }}" data-note="{{ $account->note }}">详情</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="bsphp-table-empty">无数据</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                @endif
                <x-slot:footer>
                    <span>共 {{ $users->total() }} 条数据</span>
                    @if($users->hasPages())<div class="pager">{{ $users->links() }}</div>@else<span>已显示全部数据</span>@endif
                </x-slot:footer>
            </x-admin.table-frame>

            <x-admin.window-dialog class="user-bulk-dialog" data-user-bulk-dialog content-class="user-bulk-dialog-card">
                    <x-slot:heading><small>BULK OPERATION</small><h3>应用账号批量维护</h3></x-slot:heading>
                    <div class="user-bulk-dialog-body">
                        <div class="bulk-mode-line"><b>A.ID批量处理</b><span class="bulk-radio is-checked"></span><strong>通过索引ID维护</strong></div>
                        <label class="bulk-id-line"><strong>ID：</strong><input data-user-bulk-id-display readonly><small>已选择 <b data-user-bulk-count>0</b> 个软件用户</small></label>
                        <div class="bulk-mode-line"><b>B.筛选选处理</b><span class="bulk-radio"></span><strong>通过范围选择维护</strong></div>
                        <div class="bulk-maintenance-actions"><b>选择维护功能</b><div>
                            @foreach(['add_time'=>'添加时间N(秒/点)','subtract_time'=>'减少时间N(秒/点)','freeze'=>'冻结账号','unfreeze'=>'解除冻结','set_expiry'=>'到期时间N(点/时间截)','bind_feature'=>'绑定特征N','delete'=>'删除(谨慎操作)'] as $actionValue=>$actionLabel)
                                <label><input type="radio" name="action" value="{{ $actionValue }}" required><span>{{ $actionLabel }}</span></label>
                            @endforeach
                        </div><small>选择要执行的结果功能</small></div>
                        <label class="bulk-value-line" data-user-bulk-value-row hidden><strong>维护值 N：</strong><input name="action_value" data-user-bulk-value><small data-user-bulk-value-help></small></label>
                        <p class="bulk-warning"><strong>注意</strong>在批量操作数据库时请先备份相关数据库，数据无价。</p>
                    </div>
                    <footer><button type="button" data-user-bulk-close>取消</button><button class="button button-primary" type="submit">确认操作</button></footer>
            </x-admin.window-dialog>
        </form>
    </section>

    <x-admin.window-dialog class="bsphp-user-detail-card" data-user-detail-dialog :workspace="false">
        <x-slot:heading><small>用户详情</small><h3 data-user-detail-title>编辑软件用户</h3></x-slot:heading>
        <form method="post" data-user-detail-form>
            @csrf @method('PUT')
            <div class="bsphp-user-detail-fields">
                <label><span>到期时间</span><input type="datetime-local" name="expires_at"></label>
                <label><span>绑定特征</span><input name="bound_device" maxlength="160" placeholder="后台显示完整特征"></label>
                <label><span>账号同时在线数</span><input type="number" name="account_concurrency_limit" min="-1" max="100" required><small>0 使用软件默认，-1 关闭此项</small></label>
                <label><span>机器同时使用量</span><input type="number" name="machine_concurrency_limit" min="-1" max="100" required><small>0 使用软件默认，-1 关闭此项</small></label>
                <label class="detail-note"><span>备注</span><textarea name="note" maxlength="500" rows="4"></textarea></label>
            </div>
            <footer><button type="button" data-user-detail-close>取消</button><button class="button button-primary" type="submit">确认保存</button></footer>
        </form>
    </x-admin.window-dialog>
@endsection
