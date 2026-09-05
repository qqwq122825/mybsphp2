@extends('agent.layout')
@section('title', '批量冻结')
@section('content')
<section class="agent-panel agent-form-panel">
    <header><h1>批量冻结</h1></header>
    <div class="agent-form-tip">输入卡密即可处理。未使用卡密会直接封卡；已使用卡密会弹出确认，可选择冻结用户或删除卡密。冻结用户不会改到期时间，解冻后原截止时间保持不变。</div>
    <form method="post" action="{{ route('agent.tools.freeze.run') }}" class="agent-business-form" data-agent-freeze-form>
        @csrf
        <label><strong>卡密</strong><textarea name="credential" rows="4" placeholder="输入完整卡号，多个卡密用空格或换行分隔" required>{{ old('credential', $credential) }}</textarea><small>只处理当前代理名下的充值卡</small></label>
        <input type="hidden" name="activated_action" value="" data-agent-activated-action>
        <div class="agent-form-actions"><button type="submit">确认冻结</button></div>
    </form>
    @if($results !== [])
        <section class="agent-tool-results">
            @foreach($results as $line)<p>{{ $line }}</p>@endforeach
        </section>
    @endif
</section>
@if($pending->isNotEmpty())
    <div class="agent-modal" data-agent-used-modal>
        <div class="agent-modal-card" role="dialog" aria-modal="true" aria-labelledby="agent-used-title">
            <h2 id="agent-used-title">卡密已经使用</h2>
            <p>以下卡密已激活。请选择冻结用户，或删除卡密。冻结用户后无法登录，到期时间保持不变；删除卡密会作废该卡并踢下线。</p>
            <ul>
                @foreach($pending as $card)
                    <li><code>{{ $card->code_ciphertext }}</code> · {{ $card->application->name }} · {{ $card->cardType->name }}</li>
                @endforeach
            </ul>
            <div class="agent-modal-actions">
                <button type="button" data-agent-used-action="freeze_user">冻结用户</button>
                <button type="button" class="is-danger" data-agent-used-action="delete_card">删除卡密</button>
                <button type="button" class="is-ghost" data-agent-used-close>取消</button>
            </div>
        </div>
    </div>
@endif
@endsection
@push('scripts')
<script>(()=>{const form=document.querySelector('[data-agent-freeze-form]'),action=form?.querySelector('[data-agent-activated-action]'),modal=document.querySelector('[data-agent-used-modal]');modal?.querySelectorAll('[data-agent-used-action]').forEach((button)=>{button.addEventListener('click',()=>{if(!form||!action)return;action.value=button.getAttribute('data-agent-used-action')||'';form.submit();});});modal?.querySelector('[data-agent-used-close]')?.addEventListener('click',()=>modal.remove());})();</script>
@endpush
