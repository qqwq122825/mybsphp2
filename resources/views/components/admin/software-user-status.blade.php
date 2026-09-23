@props(['user'])
@php
    $status = $user->softwareUserStatus();
    $label = match ($status) {
        'active' => '正常',
        'frozen' => '已冻结',
        'revoked' => '已撤销',
        'expired' => '已到期',
        'disabled' => '已停用',
        'inventory', 'issued' => '未激活',
        'consumed' => '已使用',
        default => '未知状态',
    };
@endphp
<span class="status-tag status-{{ $status }}">{{ $label }}</span>
