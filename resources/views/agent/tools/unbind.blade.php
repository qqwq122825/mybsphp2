@extends('agent.layout')
@section('title', '解除用户绑定')
@section('content')
<section class="agent-panel agent-form-panel agent-unbind-panel">
    <header><h1>解除绑定</h1></header>
    <form method="post" action="{{ route('agent.tools.unbind.run') }}" class="agent-business-form">
        @csrf
        <label><strong>软件选择</strong><select name="application_id" required><option value="">请选择软件</option>@foreach($applications as $application)<option value="{{ $application->id }}" @selected((string)old('application_id') === (string)$application->id)>{{ $application->name }}</option>@endforeach</select><small>仅显示当前代理有卡的软件</small></label>
        <label><strong>账号/卡</strong><input name="credential" value="{{ old('credential') }}" placeholder="输入用户账户/完整卡号" required><small>当前支持充值卡号</small></label>
        <label><strong>新绑定</strong><input name="new_binding" value="{{ old('new_binding') }}" placeholder="新绑定内容，不绑定新设备请留空"><small>留空即解除绑定</small></label>
        <div class="agent-form-actions"><button type="submit">确认提交</button></div>
    </form>
</section>
@endsection
