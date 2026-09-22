<div class="admin-form-grid software-config-panel" id="software-panel-proxy" role="tabpanel" data-config-panel="proxy" hidden>
    @php
        $proxyLink = $proxyLink ?? null;
        $proxyPage = [
            'action' => route('admin.applications.proxy-link.update', ['adminPath' => $adminPath, 'application' => $application]),
            'method' => 'post', 'fields' => [['_token', csrf_token()]],
            'enabled' => (string) (int) ($proxyLink?->enabled ?? false),
            'address' => $proxyLink?->address(),
        ];
    @endphp
    <script type="application/json" data-proxy-link-config>{!! json_encode($proxyPage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <section class="dashboard-panel form-panel form-panel-wide">
        <div class="panel-body stack-form">
            <form method="post" action="{{ $proxyPage['action'] }}">
                @csrf
                <input type="hidden" name="action" value="save">
                <label class="field"><span>启用代理联动</span>
                    <input type="hidden" name="enabled" value="0">
                    <span class="application-delete-switch">
                        <input type="checkbox" name="enabled" value="1" role="switch" aria-label="启用代理联动"
                               @checked($proxyLink?->enabled) onchange="this.form.requestSubmit()">
                        <span>启用</span><i aria-hidden="true">✓</i>
                    </span>
                    <small>开启后自动生成同步地址。</small>
                </label>
            </form>
            @if($proxyLink)
                <div class="connection-row connection-row-address">
                    <div><span>同步地址</span><code>{{ $proxyLink->address() }}</code></div>
                    <button class="button" type="button" data-copy="{{ $proxyLink->address() }}">复制地址</button>
                </div>
                <small>将此地址填入 DFMProxy，每 30 秒自动同步账号。此地址包含同步凭证，请勿公开。</small>
            @else
                <p>尚未生成联动地址。开启上方开关即可生成。</p>
            @endif
        </div>
    </section>
</div>
