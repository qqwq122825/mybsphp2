@extends('admin.layout')

@section('title', $application->name.' · 现卡制作')

@section('content')
    <header class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">MAKE CARDS</p>
            <h1>现卡制作 · {{ $application->name }}</h1>
            <p>选择当前软件的充值卡类型，批量生成随机卡号和密码。</p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.applications.index', ['adminPath' => $adminPath]) }}">返回软件管理</a>
    </header>

    @if($generatedCards !== [])
        <section class="secret-panel credential-panel">
            <div><span>GENERATED CREDENTIALS · 明文仅展示本次</span><strong>{{ count($generatedCards) }} 张卡</strong></div>
            <textarea readonly rows="{{ min(12, count($generatedCards) + 1) }}">@foreach($generatedCards as $credential){{ $credential['code'] }} | {{ $credential['secret'] }}
@endforeach</textarea>
        </section>
    @endif

    @if($cardTypes->isEmpty())
        <section class="dashboard-panel data-panel prerequisite-panel">
            <div class="empty-activity">
                <b>!</b>
                <strong>请先添加充值卡类型</strong>
                <p>当前软件还没有卡串类型，建立类型后再进行现卡制作。</p>
                <a class="button button-primary" href="{{ route('admin.cards.index', ['adminPath' => $adminPath, 'application' => $application->getKey()]) }}#card-types">添加充值卡类型</a>
            </div>
        </section>
    @else
        <section class="management-grid single-management-grid">
            <form class="dashboard-panel form-panel" method="post" action="{{ route('admin.card-batches.store', ['adminPath' => $adminPath]) }}">
                @csrf
                <header><div><span>GENERATE</span><h2>批量制作现卡</h2></div><em>1–500 张</em></header>
                <div class="panel-body stack-form">
                    <label class="field"><span>充值卡类型</span>
                        <select name="card_type_id" required>
                            @foreach($cardTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} · {{ $type->code }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field"><span>制作数量</span><input type="number" name="quantity" min="1" max="500" value="10" required></label>
                    <label class="field"><span>批次备注</span><textarea name="note" rows="3" maxlength="500" placeholder="例如：本月线下现卡"></textarea></label>
                    <div class="info-panel"><span>i</span><p>卡号与密码使用系统随机源生成，明文只在本次页面展示。</p></div>
                    <button class="button button-primary" type="submit">确认制作</button>
                </div>
            </form>
        </section>
    @endif
@endsection
