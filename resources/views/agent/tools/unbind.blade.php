@extends('agent.layout')
@section('title', '解除用户绑定')
@section('content')
<section class="agent-panel agent-form-panel agent-unbind-panel">
    <header><h1>解除绑定</h1></header>
    <div class="agent-form-tip">输入完整卡密即可解除设备绑定，无需选择软件。</div>
    <form method="post" action="{{ route('agent.tools.unbind.run') }}" class="agent-business-form">
        @csrf
        <label><strong>卡密</strong><input name="credential" value="{{ old('credential') }}" placeholder="输入完整卡号" required autofocus><small>只支持当前代理名下的充值卡</small></label>
        <div class="agent-form-actions"><button type="submit">确认解绑</button></div>
    </form>
</section>
@endsection
