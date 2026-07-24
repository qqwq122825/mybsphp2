@extends('agent.layout')
@section('title', '财务管理')
@section('content')
<section class="agent-panel agent-finance-panel">
    <header><div><h1>财务管理</h1><p>当前余额：¥ {{ number_format($agent->agent_balance_cents / 100, 2) }}</p></div></header>
    <x-admin.table-frame frame-class="agent-table-frame">
        <x-slot:toolbar><div><strong>余额流水</strong></div><span>共 {{ $transactions->total() }} 条</span></x-slot:toolbar>
        <table><thead><tr><th>时间</th><th>类型</th><th>变动金额</th><th>变动后余额</th><th>备注</th></tr></thead><tbody>
        @forelse($transactions as $transaction)<tr><td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td><td>{{ ['admin_recharge'=>'平台充值','admin_adjustment'=>'平台调整','card_purchase'=>'余额制卡'][$transaction->type] ?? $transaction->type }}</td><td class="{{ $transaction->amount_cents >= 0 ? 'agent-money-in' : 'agent-money-out' }}">{{ $transaction->amount_cents >= 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2) }}</td><td>{{ number_format($transaction->balance_after_cents / 100, 2) }}</td><td>{{ $transaction->note ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="empty-cell">暂无余额流水。</td></tr>@endforelse
        </tbody></table>
        @if($transactions->hasPages())
            <x-slot:footer><div class="pager">{{ $transactions->links() }}</div></x-slot:footer>
        @endif
    </x-admin.table-frame>
</section>
@endsection
