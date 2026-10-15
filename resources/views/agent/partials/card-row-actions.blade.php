@php
    $used = $card->activated_at !== null || in_array($card->status, ['active', 'expired', 'consumed'], true);
    $revoked = $card->status === 'revoked';
    $locked = $card->card_is_frozen || $card->user_status === 'frozen';
@endphp
@if(! $revoked)
    @if(! $used)
        <form method="post" action="{{ route('agent.cards.action', $card) }}">
            @csrf
            <input type="hidden" name="action" value="{{ $locked ? 'unfreeze' : 'freeze' }}">
            <button type="submit">{{ $locked ? '解冻' : '冻结' }}</button>
        </form>
    @endif
    @if($card->agentDeleteEligible())
        <form method="post" action="{{ route('agent.cards.action', $card) }}">
            @csrf
            <input type="hidden" name="action" value="delete_card">
            <button type="submit" class="is-danger">删除</button>
        </form>
    @endif
    @if($used)
        <form method="post" action="{{ route('agent.cards.action', $card) }}">
            @csrf
            <input type="hidden" name="action" value="unbind">
            <button type="submit">解绑</button>
        </form>
    @endif
@endif
