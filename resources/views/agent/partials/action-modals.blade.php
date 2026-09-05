@if($deleteCard ?? null)
    <div class="agent-modal" data-agent-delete-modal>
        <div class="agent-modal-card" role="dialog" aria-modal="true" aria-labelledby="agent-delete-title">
            <h2 id="agent-delete-title">确认删除</h2>
            <p>删除后用户无法继续登录，并将退回制卡金额 {{ $deleteCard['refund'] }} 元。</p>
            <ul>
                <li><code>{{ $deleteCard['code'] }}</code> · {{ $deleteCard['application'] }} · {{ $deleteCard['type'] }}</li>
            </ul>
            <form method="post" action="{{ route('agent.cards.action', $deleteCard['id']) }}" class="agent-modal-actions">
                @csrf
                <input type="hidden" name="action" value="delete_card">
                <input type="hidden" name="confirmed" value="1">
                <button type="submit" class="is-danger">确认删除并退款</button>
                <button type="button" class="is-ghost" data-agent-modal-close>取消</button>
            </form>
        </div>
    </div>
@endif
@if($unbindCard ?? null)
    <div class="agent-modal" data-agent-unbind-modal>
        <div class="agent-modal-card" role="dialog" aria-modal="true" aria-labelledby="agent-unbind-title">
            <h2 id="agent-unbind-title">确认解绑</h2>
            <p>是否扣除解绑时间？{{ $unbindCard['deduction'] }}。不扣时间则只解除设备绑定，到期时间保持不变。</p>
            <ul>
                <li><code>{{ $unbindCard['code'] }}</code> · {{ $unbindCard['application'] }} · {{ $unbindCard['type'] }}</li>
            </ul>
            <form method="post" action="{{ route('agent.cards.action', $unbindCard['id']) }}" class="agent-modal-actions">
                @csrf
                <input type="hidden" name="action" value="unbind">
                <button type="submit" name="deduct_time" value="1">扣除时间</button>
                <button type="submit" name="deduct_time" value="0">不扣时间</button>
                <button type="button" class="is-ghost" data-agent-modal-close>取消</button>
            </form>
        </div>
    </div>
@endif
