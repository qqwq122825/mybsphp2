@php
    $peers = $peerPresence ?? [
        'url' => route('admin.applications.peer-presence', ['adminPath' => $adminPath, 'application' => $application]),
        'edit_url' => route('admin.applications.edit', ['adminPath' => $adminPath, 'application' => $application]),
        'enabled' => false, 'rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 25, 'last_page' => 1,
        'pending_names' => 0, 'error' => null, 'refreshed_at' => '',
    ];
@endphp
<section class="peer-presence" data-peer-presence aria-label="在线同行名单" @if(!old('peer_display_enabled', $application->peer_display_enabled ?? false)) hidden @endif>
    <script type="application/json" data-peer-presence-config>@json($peers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    <div class="peer-presence-toolbar">
        <h3>在线同行 <small data-peer-total>共 {{ $peers['total'] }} 个昵称</small></h3>
        <a class="button button-secondary" data-peer-refresh href="{{ $peers['edit_url'] }}?config_tab=entertainment&peer_page={{ $peers['page'] }}">刷新名单</a>
    </div>
    <p class="peer-presence-note">仅显示当前软件，按最近心跳倒序；同名合并。10 分钟心跳，20 分钟未更新自动下线。状态以已保存的开关为准。</p>
    <p class="peer-presence-error" data-peer-error role="alert" @if(!$peers['error']) hidden @endif>{{ $peers['error'] }}</p>
    <p class="peer-presence-note" data-peer-pending @if(!$peers['pending_names']) hidden @endif>有 {{ $peers['pending_names'] }} 条旧记录等待下次心跳补全昵称。</p>
    <div class="peer-presence-scroll">
        <table class="peer-presence-table">
            <thead><tr><th scope="col">同行名字</th><th scope="col">最近心跳时间（北京时间）</th></tr></thead>
            <tbody data-peer-rows>
                @forelse($peers['rows'] as $peer)
                    <tr><td>{{ $peer['game_name'] }}</td><td>{{ $peer['last_seen_at'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="peer-presence-empty">{{ $peers['enabled'] ? '暂无在线同行，进入对局上报后显示。' : '同行显示已关闭，开启并保存后接收上报。' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="peer-presence-pagination">
        <small data-peer-refreshed>名单刷新于 {{ $peers['refreshed_at'] }}（北京时间）</small>
        <div data-peer-pager>
            @if($peers['page'] > 1)
                <a data-peer-page="{{ $peers['page'] - 1 }}" href="{{ $peers['edit_url'] }}?config_tab=entertainment&peer_page={{ $peers['page'] - 1 }}">上一页</a>
            @endif
            <span>第 {{ $peers['page'] }} / {{ $peers['last_page'] }} 页</span>
            @if($peers['page'] < $peers['last_page'])
                <a data-peer-page="{{ $peers['page'] + 1 }}" href="{{ $peers['edit_url'] }}?config_tab=entertainment&peer_page={{ $peers['page'] + 1 }}">下一页</a>
            @endif
        </div>
    </div>
</section>
