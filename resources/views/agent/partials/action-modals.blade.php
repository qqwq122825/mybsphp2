@if($usedCard ?? null)
    <div class="agent-modal" data-agent-used-modal>
        <div class="agent-modal-card" role="dialog" aria-modal="true" aria-labelledby="agent-used-title">
            <h2 id="agent-used-title">卡密已经使用</h2>
            <p>该卡密已激活。请选择冻结用户或删除卡密。冻结用户后无法登录，到期时间保持不变；删除卡密会作废该卡并踢下线。</p>
            <ul>
                <li><code>{{ $usedCard['code'] }}</code> · {{ $usedCard['application'] }} · {{ $usedCard['type'] }}</li>
            </ul>
            <form method="post" action="{{ route('agent.cards.action', $usedCard['id']) }}" class="agent-modal-actions">
                @csrf
                <button type="submit" name="action" value="freeze_user">冻结用户</button>
                <button type="submit" name="action" value="delete_card" class="is-danger">删除卡密</button>
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
