@extends('agent.layout')
@section('title', '批量解冻')
@section('content')
<section class="agent-panel agent-form-panel">
    <header><h1>批量解冻</h1></header>
    <div class="agent-form-tip">输入卡密即可解冻。未激活卡密会解除封卡；已激活用户会恢复登录。到期时间保持原来的截止时间，不会补回冻结期间的时长。</div>
    <form method="post" action="{{ route('agent.tools.unfreeze.run') }}" class="agent-business-form">
        @csrf
        <label><strong>卡密</strong><textarea name="credential" rows="4" placeholder="输入完整卡号，多个卡密用空格或换行分隔" required>{{ old('credential', $credential) }}</textarea><small>只处理当前代理名下的充值卡</small></label>
        <div class="agent-form-actions"><button type="submit">确认解冻</button></div>
    </form>
    @if($results !== [])
        <section class="agent-tool-results">
            @foreach($results as $line)<p>{{ $line }}</p>@endforeach
        </section>
    @endif
</section>
@endsection
