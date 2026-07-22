@extends('admin.layout')

@section('title', $title)

@section('content')
@php
    $cardTools = [
        'batch-query' => ['field' => '需要查询的充值卡', 'button' => '查询', 'hint' => '粘贴卡密后，系统会自动识别所属软件并返回卡片状态。'],
        'batch-freeze' => ['field' => '需要封卡的充值卡', 'button' => '确认封卡', 'hint' => '无需选择软件，系统会根据卡密自动识别所属软件并立即冻结。'],
        'batch-unfreeze' => ['field' => '需要解冻的充值卡', 'button' => '确认解冻', 'hint' => '无需选择软件，系统会根据卡密自动识别所属软件并立即解冻。'],
        'batch-delete' => ['field' => '需要删除的充值卡', 'button' => '确认删除', 'hint' => '无需选择软件，系统会根据卡密自动识别所属软件并将卡片设为已删除。'],
    ];
    $cardStatusLabels = [
        'inventory' => '库存', 'issued' => '已发放', 'active' => '已激活',
        'expired' => '已到期', 'consumed' => '已使用', 'revoked' => '已删除',
    ];
@endphp
<section class="dashboard-panel operation-plugin-panel">
    <header><div><h2>{{ $title }}</h2><small>运营插件</small></div></header>

    @if(isset($cardTools[$tool]))
        <form method="post" action="{{ route('admin.operation-plugins.show', ['adminPath' => $adminPath, 'tool' => $tool]) }}" class="operation-plugin-query-form">
            @csrf
            <div class="operation-plugin-form-heading">
                <label for="operation-query-codes">{{ $cardTools[$tool]['field'] }}</label>
                <span>{{ $cardTools[$tool]['hint'] }}</span>
            </div>
            <textarea id="operation-query-codes" name="codes" rows="10" placeholder="粘贴卡密，一行一张，最多处理 500 张">{{ old('codes', implode("\n", $codes)) }}</textarea>
            @error('codes')<p class="form-error">{{ $message }}</p>@enderror
            @if($tool === 'batch-delete')
                <label class="operation-plugin-confirm">
                    <input type="checkbox" name="confirmed" value="1" @checked(old('confirmed'))>
                    <span>我已确认删除以上卡密；删除后卡片将不可继续使用。</span>
                </label>
                @error('confirmed')<p class="form-error">请勾选删除确认。</p>@enderror
            @endif
            <button class="button button-primary" type="submit" @if($tool === 'batch-delete') onclick="return confirm('确定删除输入的充值卡吗？')" @endif>{{ $cardTools[$tool]['button'] }}</button>
        </form>

        @if($queryResults->isNotEmpty())
            <x-admin.table-frame frame-class="operation-query-results">
                <x-slot:toolbar><span>处理结果：{{ $queryResults->count() }} 条</span></x-slot:toolbar>
                <table><thead><tr><th>充值卡</th><th>自动识别的软件</th><th>卡类型</th><th>当前状态</th><th>操作结果</th></tr></thead><tbody>
                @foreach($queryResults as $result)
                    @php
                        $card = $result['card'];
                    @endphp
                    <tr>
                        <td><code>{{ $result['code'] }}</code></td>
                        <td>{{ $card?->application?->name ?? '—' }}</td>
                        <td>{{ $card?->cardType?->name ?? '—' }}</td>
                        <td>
                            @if($card)
                                {{ $cardStatusLabels[$card->status] ?? $card->status }}{{ $card->card_is_frozen ? ' · 已冻结' : '' }}
                            @else
                                不存在
                            @endif
                        </td>
                        <td><span class="operation-result {{ $card ? 'is-success' : 'is-missing' }}">{{ $result['result'] }}</span></td>
                    </tr>
                @endforeach
                </tbody></table>
            </x-admin.table-frame>
        @endif
    @elseif($tool === 'monitor')
        <x-admin.table-frame frame-class="operation-monitor-table">
            <x-slot:toolbar><span>最近接口与后台操作记录</span></x-slot:toolbar>
            <table><thead><tr><th>时间</th><th>动作</th><th>对象</th><th>结果</th><th>IP</th></tr></thead><tbody>
            @forelse($recentLogs as $log)
                <tr><td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td><td>{{ $log->action }}</td><td>{{ $log->target_type }}</td><td>{{ $log->result }}</td><td>{{ $log->ip ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="5" class="empty-cell">暂无监控记录。</td></tr>
            @endforelse
            </tbody></table>
        </x-admin.table-frame>
    @endif
</section>
@endsection
