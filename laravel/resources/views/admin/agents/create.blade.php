@extends('admin.layout')

@section('title', '添加代理账号')

@section('content')
<section class="dashboard-panel administrator-create-panel">
    <header>
        <div>
            <h2>添加代理账号</h2>
            <small>创建独立的代理后台登录账号</small>
        </div>
        <a class="button button-secondary" href="{{ route('admin.agents.index', ['adminPath' => $adminPath]) }}">返回代理账号列表</a>
    </header>

    <form method="post" action="{{ route('admin.agents.store', ['adminPath' => $adminPath]) }}">
        @csrf
        <div class="administrator-create-rows">
            <label class="administrator-create-row">
                <strong>代理账号</strong>
                <input name="username" value="{{ old('username') }}" minlength="3" maxlength="32" required
                       autocomplete="username" placeholder="3-32位，字母开头">
                <small>代理后台登录账号，可使用字母、数字、下划线和短横线</small>
            </label>
            <label class="administrator-create-row">
                <strong>登录密码</strong>
                <input type="password" name="password" minlength="6" maxlength="128" required
                       autocomplete="new-password" placeholder="至少6位">
                <small>建议同时包含字母和数字</small>
            </label>
            <label class="administrator-create-row">
                <strong>确认密码</strong>
                <input type="password" name="password_confirmation" minlength="6" maxlength="128" required
                       autocomplete="new-password" placeholder="再次输入登录密码">
                <small>需要与登录密码保持一致</small>
            </label>
            <label class="administrator-create-row">
                <strong>初始余额</strong>
                <input type="number" name="initial_balance" value="{{ old('initial_balance', '0.00') }}" min="0" max="9999999" step="0.01">
                <small>代理可使用该余额按代理价制作充值卡，后续可在代理列表中调整</small>
            </label>
            <label class="administrator-create-row">
                <strong>购卡折扣</strong>
                <input type="number" name="purchase_discount" value="{{ old('purchase_discount', '10.0') }}" min="0" max="10" step="0.1">
                <small>例如 8.8 表示八八折；填 0 使用标准代理价</small>
            </label>
            <label class="administrator-create-row">
                <strong>后台备注</strong>
                <textarea name="agent_note" maxlength="500" rows="3" placeholder="选填，仅后台管理员可见">{{ old('agent_note') }}</textarea>
                <small>记录代理来源、业务说明等内部信息</small>
            </label>
            <label class="administrator-create-row">
                <strong>联系 QQ</strong>
                <input name="contact_qq" value="{{ old('contact_qq') }}" maxlength="32" placeholder="选填">
                <small>代理联系信息</small>
            </label>
            <label class="administrator-create-row">
                <strong>联系邮箱</strong>
                <input type="email" name="contact_email" value="{{ old('contact_email') }}" maxlength="255" placeholder="选填">
                <small>代理联系信息</small>
            </label>
            <label class="administrator-create-row">
                <strong>联系电话</strong>
                <input name="contact_phone" value="{{ old('contact_phone') }}" maxlength="32" placeholder="选填">
                <small>代理联系信息</small>
            </label>
            <fieldset class="administrator-create-access" data-agent-create-access>
                <legend>可用软件分配</legend>
                <input type="hidden" name="all_applications" value="0">
                <label><input type="checkbox" name="all_applications" value="1" data-agent-create-all @checked(old('all_applications', '1') === '1')> 默认全部软件</label>
                <div class="agent-create-application-groups">
                    @forelse($applications as $application)
                        <section data-agent-create-application>
                            <label><input type="checkbox" name="application_ids[]" value="{{ $application->id }}" data-agent-create-application-toggle @checked(in_array($application->id, old('application_ids', [])))> <strong>{{ $application->name }}</strong> <small>{{ $application->code }}</small></label>
                            <div>
                                @forelse($application->cardTypes as $cardType)
                                    <label><input type="checkbox" name="card_type_ids[]" value="{{ $cardType->id }}" data-agent-create-card-type @checked(in_array($cardType->id, old('card_type_ids', [])))> {{ $cardType->name }} <small>{{ $cardType->code }}</small></label>
                                @empty
                                    <small>该软件暂无可用充值卡类型</small>
                                @endforelse
                            </div>
                        </section>
                    @empty
                        <small>当前还没有软件。</small>
                    @endforelse
                </div>
                <p>如需限制代理业务范围，请取消默认全部软件，再按软件勾选允许制作的充值卡类型。</p>
            </fieldset>
            <div class="administrator-create-note">代理账号只开放代理业务权限，不会出现在后台管理员列表中。</div>
            <div class="administrator-create-actions">
                <button class="button button-primary" type="submit">确认保存</button>
                <a class="button button-secondary" href="{{ route('admin.agents.index', ['adminPath' => $adminPath]) }}">取消</a>
            </div>
        </div>
    </form>
</section>
<script>(()=>{const root=document.querySelector('[data-agent-create-access]');if(!root)return;const all=root.querySelector('[data-agent-create-all]'),groups=[...root.querySelectorAll('[data-agent-create-application]')],sync=()=>groups.forEach(group=>{const app=group.querySelector('[data-agent-create-application-toggle]'),types=[...group.querySelectorAll('[data-agent-create-card-type]')];app.disabled=all.checked;types.forEach(type=>type.disabled=all.checked||!app.checked)});all.addEventListener('change',sync);groups.forEach(group=>{const app=group.querySelector('[data-agent-create-application-toggle]'),types=[...group.querySelectorAll('[data-agent-create-card-type]')];app.addEventListener('change',()=>{types.forEach(type=>type.checked=app.checked);sync()});types.forEach(type=>type.addEventListener('change',()=>{if(type.checked)app.checked=true;sync()}))});sync()})();</script>
@endsection
