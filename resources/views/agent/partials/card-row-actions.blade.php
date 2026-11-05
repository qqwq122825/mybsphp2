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
    @if(! empty($renewal))
        @php
            $priceLabel = number_format($renewal['price'] / 100, 2, '.', '');
            $seconds = $renewal['seconds'];
            $durationLabel = $seconds % 86400 === 0 ? intdiv($seconds, 86400).' 天'
                : ($seconds % 3600 === 0 ? intdiv($seconds, 3600).' 小时'
                : ($seconds % 60 === 0 ? intdiv($seconds, 60).' 分钟' : $seconds.' 秒'));
        @endphp
        <form method="post" action="{{ route('agent.cards.action', $card) }}">
            @csrf
            <input type="hidden" name="action" value="renew">
            <input type="hidden" name="renewal_id" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
            <input type="hidden" name="renewal_price_cents" value="{{ $renewal['price'] }}">
            <input type="hidden" name="renewal_seconds" value="{{ $seconds }}">
            <button type="submit" onclick="return confirm('确认扣除 {{ $priceLabel }} 点并续费 {{ $durationLabel }}？')">续费</button>
        </form>
    @endif
    @if(($purchased ?? true) && $card->agentDeleteEligible())
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
