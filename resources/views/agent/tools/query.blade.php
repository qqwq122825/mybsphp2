@extends('agent.layout')
@section('title', '卡密查询')
@php
    $statusNames = [
        'inventory' => '未激活', 'issued' => '未激活', 'active' => '已激活',
        'expired' => '已到期', 'frozen' => '卡密已冻结', 'revoked' => '已删除', 'consumed' => '已用完',
    ];
    $timerStatusNames = [
        'none' => '非计时卡', 'pending' => '未开始计时', 'active' => '计时中',
        'paused' => '计时已暂停', 'expired' => '计时已结束',
    ];
    $queryTime = \Carbon\CarbonImmutable::now();
@endphp
@section('content')
<x-admin.table-shell title="卡密查询" subtitle="可查询系统内所有人制作的卡密；查询结果仅供查看，续费、解绑等操作请到卡管理。" panel-class="agent-query-panel">
    <x-slot:search>
        <x-admin.filter-bar :action="route('agent.tools.query')" keyword-label="卡密：" button-label="⌕" :icon-only="true">
            <x-slot:keyword>
                <textarea name="keyword" rows="5" placeholder="输入完整卡号或后4位，多个卡密用空格或换行分隔" autofocus>{{ $keyword }}</textarea>
            </x-slot:keyword>
        </x-admin.filter-bar>
    </x-slot:search>
    <x-slot:toolbar>
        <span>{{ $keyword === '' ? '输入卡密后查询' : '共 '.$cards->total().' 张' }}</span>
    </x-slot:toolbar>
    @if($cards->hasPages())
        <x-slot:footer>
            <span>共 {{ $cards->total() }} 张</span>
            <div class="pager">{{ $cards->links() }}</div>
        </x-slot:footer>
    @endif
    <table class="bsphp-card-table">
        <thead>
        <tr>
            <th>编号</th>
            <th>卡号</th>
            <th>软件名称</th>
            <th>卡类</th>
            <th>制卡人</th>
            <th>是否激活</th>
            <th>计时状态</th>
            <th>卡密冻结</th>
            <th>绑定特征</th>
            <th>卡密状态</th>
            <th>制作时间</th>
        </tr>
        </thead>
        <tbody>
        @forelse($cards as $card)
            @php
                $isActivated = $card->activated_at !== null || in_array($card->status, ['active', 'expired', 'consumed'], true);
                $expiresAt = $card->effectiveExpiresAt($queryTime);
                $cardStatus = $card->status === 'active' && $expiresAt?->lte($queryTime) ? 'expired' : $card->status;
                $timerStatus = match (true) {
                    ! $card->is_timer => 'none',
                    in_array($cardStatus, ['expired', 'revoked', 'consumed'], true) || $card->revoked_at !== null => 'expired',
                    ! $isActivated || $expiresAt === null => 'pending',
                    $card->isTimerPaused($queryTime) => 'paused',
                    default => 'active',
                };
                $isCardFrozen = $card->card_is_frozen || $card->status === 'frozen';
            @endphp
            <tr>
                <td>{{ $card->id }}</td>
                <td><code>{{ $card->code_ciphertext }}</code></td>
                <td>{{ $card->application?->name ?: '—' }}</td>
                <td>{{ $card->cardType?->name ?: '—' }}</td>
                <td>{{ $card->batch?->maker_reference ?: '系统管理员' }}</td>
                <td>{{ $isActivated ? '已激活' : '未激活' }}</td>
                <td><span class="status-tag status-{{ $timerStatus }}">{{ $timerStatusNames[$timerStatus] }}</span></td>
                <td>{{ $isCardFrozen ? '卡密已冻结' : '卡密未冻结' }}</td>
                <td class="bsphp-binding-value" title="{{ $isActivated ? ($card->bound_device_ciphertext ?: '') : '' }}">{{ $isActivated ? ($card->bound_device_ciphertext ?: '—') : '—' }}</td>
                <td>
                    <span class="status-tag status-{{ $cardStatus }}">{{ $statusNames[$cardStatus] ?? $cardStatus }}</span>
                    @if($card->user_status === 'frozen')
                        <br><span class="status-tag status-frozen">软件用户已冻结</span>
                    @endif
                </td>
                <td>{{ $card->created_at?->format('Y-m-d H:i:s') }}</td>
            </tr>
        @empty
            <tr><td colspan="11" class="bsphp-table-empty">{{ $keyword === '' ? '请输入卡密后查询。' : '未找到充值卡。' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</x-admin.table-shell>
<span hidden data-agent-pagination data-page="{{ $cards->currentPage() }}" data-total="{{ $cards->total() }}" data-per-page="{{ $cards->perPage() }}"></span>
@endsection
