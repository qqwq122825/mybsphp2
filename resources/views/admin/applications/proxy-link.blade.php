<div class="admin-form-grid software-config-panel" id="software-panel-proxy" role="tabpanel" data-config-panel="proxy" hidden>
    @php
        $proxyLink = $proxyLink ?? null;
        $proxyCredentials = $proxyCredentials ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30);
        $proxyPage = [
            'action' => route('admin.applications.proxy-link.update', ['adminPath' => $adminPath, 'application' => $application]),
            'method' => 'post', 'fields' => [['_token', csrf_token()]],
            'enabled' => (string) old('enabled', (int) ($proxyLink?->enabled ?? false)),
            'permission' => old('permission', $proxyLink?->permission ?? 'replay'),
            'address' => $proxyLink?->address(), 'path' => $proxyLink ? '/'.$proxyLink->path : null,
            'token' => $proxyLink?->token_ciphertext,
            'node' => $proxyLink?->node_name ?: '尚未接入',
            'lastSync' => $proxyLink?->last_sync_at?->format('Y-m-d H:i:s') ?? '—',
            'count' => $proxyLink?->last_user_count ?? 0,
            'previousPath' => $proxyLink?->previous_path_until?->isFuture() ? '/'.$proxyLink->previous_path : null,
            'previousUntil' => $proxyLink?->previous_path_until?->format('Y-m-d H:i:s'),
            'credentials' => $proxyCredentials->map(fn ($c) => ['subject' => $c->subject_type.':'.$c->subject_id, 'username' => $c->username, 'password' => $c->password_ciphertext])->all(),
            'search' => (string) request('proxy_search', ''),
            'searchAction' => route('admin.applications.edit', ['adminPath' => $adminPath, 'application' => $application]),
            'previousUrl' => $proxyCredentials->appends(['config_tab' => 'proxy'])->previousPageUrl(),
            'nextUrl' => $proxyCredentials->appends(['config_tab' => 'proxy'])->nextPageUrl(),
            'total' => $proxyCredentials->total(),
        ];
    @endphp
    <script type="application/json" data-proxy-link-config>{!! json_encode($proxyPage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <section class="dashboard-panel form-panel form-panel-wide">
        <header><div><span>DFMPROXY LINK</span><h2>代理联动</h2></div><em>应用 {{ $application->code }} · 单节点</em></header>
        <div class="panel-body stack-form">
            <p>启用后，将已激活卡密 / 充值账号的有效期同步到 DFMProxy。原客户端登录入口保持独立。开启后自动保存并生成专属随机地址和密钥，关闭后保留地址，再次开启沿用。</p>
            <form method="post" action="{{ route('admin.applications.proxy-link.update', ['adminPath' => $adminPath, 'application' => $application]) }}">
                @csrf
                <div class="field-grid">
                    <label class="field"><span>启用代理联动</span>
                        <input type="hidden" name="enabled" value="0">
                        <span class="application-delete-switch">
                            <input type="checkbox" name="enabled" value="1" role="switch" aria-label="启用代理联动"
                                   @checked(old('enabled', $proxyLink?->enabled))
                                   onchange="this.form.requestSubmit(this.form.querySelector('[data-proxy-save]'))">
                            <span>启用</span><i aria-hidden="true">✓</i>
                        </span>
                        <small>开启即保存并生成地址，无需另点保存。</small>
                    </label>
                    <label class="field"><span>同步用户权限</span><select name="permission">
                        @foreach(['replay' => '仅重放', 'record' => '仅录制', 'both' => '录制＋重放'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('permission', $proxyLink?->permission ?? 'replay') === $value)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                </div>
                @if(!$proxyLink)
                    <p>尚未生成联动地址。打开上方开关后，这里将显示地址、CDN 路径和节点密钥。</p>
                @endif
                @if($proxyLink)
                    <div class="connection-row connection-row-address"><div><span>联动地址（CDN 精确路径白名单，POST，无缓存）</span><code>{{ $proxyLink->address() }}</code></div><button type="button" data-copy="{{ $proxyLink->address() }}">复制地址</button></div>
                    <div class="connection-row"><div><span>CDN 匹配路径</span><code>/{{ $proxyLink->path }}</code></div><button type="button" data-copy="/{{ $proxyLink->path }}">复制路径</button></div>
                    <div class="connection-row"><div><span>节点接入密钥（仅管理员与代理节点使用）</span><code>••••••••{{ substr($proxyLink->token_ciphertext, -4) }}</code></div><button type="button" data-copy="{{ $proxyLink->token_ciphertext }}">复制密钥</button></div>
                    <p>节点：{{ $proxyLink->node_name ?: '尚未接入' }}；最近拉取：{{ $proxyLink->last_sync_at?->format('Y-m-d H:i:s') ?? '—' }}；上次拉取用户数：{{ $proxyLink->last_user_count }}。此处表示服务端已返回，实际生效状态请查看 DFMProxy。</p>
                    @if($proxyLink->previous_path_until?->isFuture())
                        <p>旧路径 /{{ $proxyLink->previous_path }} 有效至 {{ $proxyLink->previous_path_until->format('Y-m-d H:i:s') }}。</p>
                    @endif
                @endif
                <div class="form-submit-bar">
                    <button class="button button-primary" name="action" value="save" data-proxy-save>保存联动配置</button>
                    @if($proxyLink)
                        <button class="button" name="action" value="rotate_path" onclick="return confirm('生成新地址，旧地址保留 10 分钟；请同步更新 CDN 和 DFMProxy。继续？')">轮换地址（10 分钟过渡）</button>
                        <button class="button" name="action" value="rotate_path_now" onclick="return confirm('旧地址将立即失效，请准备更新 CDN 和 DFMProxy。继续？')">立即重置地址</button>
                        <button class="button" name="action" value="rotate_token" onclick="return confirm('旧密钥立即失效，并解除节点绑定。继续？')">重置密钥 / 更换节点</button>
                    @endif
                </div>
            </form>
            <small>随机地址保持固定；仅调整该路径的机器请求挑战策略，保留 CDN 流量防护及限流。首次成功同步绑定节点。断网宽限最长 300 秒，仍受原到期时间限制。空到期时间、积分卡暂不授予代理访问资格。</small>
        </div>
    </section>
    <section class="dashboard-panel form-panel form-panel-wide">
        <header><div><h2>代理账号</h2></div><em>卡密激活后可复制给用户</em></header>
        <div class="panel-body stack-form">
            <form method="get" action="{{ route('admin.applications.edit', ['adminPath' => $adminPath, 'application' => $application]) }}">
                <input type="hidden" name="config_tab" value="proxy">
                <label class="field"><span>按授权唯一 ID 精确查询（请勿填写卡密）</span><input name="proxy_search" value="{{ request('proxy_search') }}"></label>
                <button class="button" type="submit">查询</button>
            </form>
            <div class="table-wrap"><table><thead><tr><th>授权类型 / 唯一 ID</th><th>代理用户名</th><th>代理密码</th></tr></thead><tbody>
                @forelse($proxyCredentials as $credential)
                    <tr><td>{{ $credential->subject_type }} / {{ $credential->subject_id }}</td><td>{{ $credential->username }}</td><td><button type="button" data-copy="{{ $credential->password_ciphertext }}">复制密码</button></td></tr>
                @empty
                    <tr><td colspan="3">暂无代理账号，启用后请在 DFMProxy 执行同步。</td></tr>
                @endforelse
            </tbody></table></div>
            {{ $proxyCredentials->appends(['config_tab' => 'proxy'])->links() }}
            <small>卡密模式：账号＝密码＝卡密；账号充值模式：独立代理码同时作为账号和密码。到期后自动删除 DFMProxy 对应用户，后台卡密与历史记录保留，续期后同步恢复。实时资格以授权状态与到期时间为准。</small>
        </div>
    </section>
</div>
