@extends('admin.layout')

@section('title', '充值卡列表')

@php
    $statusNames = [
        'inventory' => '未使用', 'issued' => '已发出', 'active' => '已使用',
        'expired' => '已到期', 'frozen' => '已冻结', 'revoked' => '已删除', 'consumed' => '已用完',
    ];
@endphp

@section('content')
    <section class="dashboard-panel bsphp-card-list-panel">
        <header class="bsphp-card-list-header">
            <h2>充值卡列表{{ $applicationFilter ? ' - '.$applicationFilter->name : '' }}</h2>
        </header>

        <x-admin.filter-bar button-label="⌕" :icon-only="true" class="admin-filter-bar--extended"
            :action="route('admin.cards.index', ['adminPath' => $adminPath])">
            <x-slot:hidden>
                @if($applicationFilter)<input type="hidden" name="application" value="{{ $applicationFilter->id }}">@endif
            </x-slot:hidden>
            <x-slot:field><select name="search_field" aria-label="搜索字段">
                <option value="code" @selected(request('search_field', 'code') === 'code')>充值卡号</option>
                <option value="id" @selected(request('search_field') === 'id')>编号</option>
                <option value="batch" @selected(request('search_field') === 'batch')>批次</option>
            </select></x-slot:field>
            <x-slot:keyword><input name="keyword" value="{{ request('keyword') }}" placeholder="请输入" aria-label="搜索关键字"></x-slot:keyword>
        </x-admin.filter-bar>

        <form class="bsphp-card-table-wrap uses-admin-data-table" method="post" action="{{ route('admin.cards.bulk-action', ['adminPath' => $adminPath]) }}">
            @csrf
            <x-admin.table-frame frame-class="recharge-card-table-component">
                <x-slot:toolbar>
                    <div>
                    <button type="button">批量处理</button>
                    <button type="submit" name="action" value="used">设已使用</button>
                    <button type="submit" name="action" value="unused">设未使用</button>
                    <button type="submit" name="action" value="freeze">冻结</button>
                    <button type="submit" name="action" value="unfreeze">解冻</button>
                    <button type="submit" name="action" value="delete">删除</button>
                    <button type="button">正常导出充值卡</button>
                    </div>
                    <span>共 {{ $cards->total() }} 张</span>
                </x-slot:toolbar>
                <table class="bsphp-card-table">
                    <thead>
                    <tr>
                        <th><input type="checkbox" aria-label="全选" data-card-select-all></th>
                        <th>编号</th>
                        <th>卡号</th>
                        <th>冻结状态</th>
                        <th>充值状态</th>
                        <th>制作时间</th>
                        <th>卡值</th>
                        <th>制卡人</th>
                        <th>充值时间</th>
                        <th>充值用户</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cards as $card)
                        <tr>
                            <td><input type="checkbox" name="card_ids[]" value="{{ $card->id }}" aria-label="选择第 {{ $card->id }} 张卡" data-card-select></td>
                            <td>{{ $card->id }}</td>
                            <td><code>{{ $card->code_ciphertext }}</code></td>
                            <td>{{ $card->card_is_frozen ? '已冻结' : '正常' }}</td>
                            <td><span class="status-tag status-{{ $card->status }}">{{ $statusNames[$card->status] ?? $card->status }}</span></td>
                            <td>{{ $card->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($card->cardType->benefit_kind === 'duration')
                                    {{ $card->cardType->duration_value }}{{ ['minute'=>'分钟','hour'=>'小时','day'=>'天','month'=>'月','year'=>'年'][$card->cardType->duration_unit] ?? '天' }}
                                @elseif($card->cardType->benefit_kind === 'points')
                                    {{ $card->cardType->points_amount }} 点
                                @else
                                    固定到期
                                @endif
                            </td>
                            <td>{{ $card->batch->maker_reference ?: '管理员' }}</td>
                            <td>{{ $card->activated_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                            <td>{{ $card->bound_device_ciphertext ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="bsphp-table-empty">无数据</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <x-slot:footer>
                    <span>共 {{ $cards->total() }} 张</span>
                    @if($cards->previousPageUrl())<a href="{{ $cards->previousPageUrl() }}">上一页</a>@endif
                    <b>第 {{ $cards->currentPage() }} / {{ $cards->lastPage() }} 页</b>
                    @if($cards->nextPageUrl())<a href="{{ $cards->nextPageUrl() }}">下一页</a>@endif
                </x-slot:footer>
            </x-admin.table-frame>
        </form>
    </section>
@endsection
