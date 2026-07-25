@extends('admin.layout')

@section('title', '快速用户操作')

@section('content')
<section class="dashboard-panel quick-operation-panel">
    <header class="quick-operation-heading">
        <div>
            <span>QUICK OPERATION</span>
            <h2>快速加时与卡密查询</h2>
            <p>输入完整充值卡号，一步查询卡密、软件用户或充值账号。</p>
        </div>
    </header>

    <form class="quick-operation-search" method="post"
          action="{{ route('admin.quick-operations.search', ['adminPath' => $adminPath]) }}">
        @csrf
        <label for="quick-credential">充值卡号</label>
        <div>
            <input id="quick-credential" name="credential" value="{{ old('credential') }}"
                   maxlength="255" autocomplete="off" spellcheck="false"
                   placeholder="粘贴用户发来的完整充值卡号" required autofocus>
            <button class="button button-primary" type="submit">立即查询</button>
        </div>
        <small>卡号仅用于服务端精确匹配，不会显示在页面地址中。</small>
    </form>

    @if($card)
        @php
            $isAccount = $card->application?->mode === 'account_recharge';
            $isCardUser = $target instanceof \App\Models\Card;
            $isAccountUser = $target instanceof \App\Models\ApplicationAccount;
            $targetStatus = $target ? ($isAccountUser ? $target->status : $target->user_status) : null;
            $statusLabels = [
                'inventory' => '库存',
                'issued' => '已发放',
                'active' => '已激活',
                'expired' => '已到期',
                'consumed' => '已充值使用',
                'frozen' => '已冻结',
                'revoked' => '已删除/作废',
            ];
        @endphp
        <div class="quick-operation-result">
            <section class="quick-card-summary">
                <header>
                    <div>
                        <span class="quick-result-badge">{{ $isAccount ? '账号登录模式' : '卡串登录模式' }}</span>
                        <h3>{{ $card->application?->name ?? '软件已删除' }}</h3>
                    </div>
                    <strong class="status-tag status-{{ $card->status }}">{{ $statusLabels[$card->status] ?? $card->status }}</strong>
                </header>
                <dl>
                    <div><dt>充值卡</dt><dd><code>{{ $card->code_ciphertext }}</code></dd></div>
                    <div><dt>卡类型</dt><dd>{{ $card->cardType?->name ?? '原卡类型已删除' }}</dd></div>
                    <div><dt>卡编号</dt><dd>#{{ $card->id }}</dd></div>
                    <div><dt>制卡批次</dt><dd>{{ $card->batch?->batch_no ?? '—' }}</dd></div>
                    <div><dt>发卡来源</dt><dd>{{ $card->batch?->source === 'sale' ? '代理发卡' : '后台制卡' }}</dd></div>
                    <div><dt>制卡账号</dt><dd>{{ $card->batch?->maker_reference ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="quick-user-summary {{ $target ? 'has-user' : 'is-empty' }}">
                <header>
                    <div>
                        <span>查询结果</span>
                        <h3>
                            @if($isAccountUser)
                                账号：{{ $target->username }}
                            @elseif($isCardUser)
                                已查询到卡串用户
                            @else
                                暂未关联软件用户
                            @endif
                        </h3>
                    </div>
                    @if($target)
                        <b>{{ $targetStatus === 'frozen' ? '冻结' : ($targetStatus === 'revoked' ? '已删除' : '可操作') }}</b>
                    @endif
                </header>
                @if($target)
                    <dl>
                        <div><dt>用户类型</dt><dd>{{ $isAccountUser ? '用户名账号' : '充值卡用户' }}</dd></div>
                        @if($isAccountUser)<div><dt>用户名</dt><dd>{{ $target->username }}</dd></div>@endif
                        <div><dt>到期时间</dt><dd>{{ $target->expires_at?->format('Y-m-d H:i:s') ?? '永久/未设置' }}</dd></div>
                        <div><dt>剩余时间</dt><dd>{{ $target->expires_at ? ($target->expires_at->isFuture() ? $target->expires_at->diffForHumans() : '已到期') : '永久' }}</dd></div>
                        <div><dt>绑定特征</dt><dd title="{{ $target->bound_device_ciphertext }}">{{ $target->bound_device_mask ?: ($target->bound_device_ciphertext ?: '—') }}</dd></div>
                    </dl>
                @else
                    <p>{{ $isAccount ? '该充值卡尚未被账号充值使用，因此还没有可查询的用户名。' : '该卡尚未激活，当前可查看发卡来源或直接删除充值卡。' }}</p>
                @endif
            </section>

            @if($target)
                <section class="quick-time-card">
                    <div>
                        <span>快速加时间</span>
                        <h3>从当前到期时间继续增加</h3>
                        <p>用户已经到期时，将从现在开始重新计算。</p>
                    </div>
                    <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                        @csrf
                        <input type="hidden" name="card_id" value="{{ $card->id }}">
                        <input type="hidden" name="action" value="add_time">
                        <label><input type="number" name="days" min="1" max="3650" value="7" data-quick-days-input required><span>天</span></label>
                        <button class="button button-primary" type="submit">确认加时</button>
                        <div class="quick-day-presets">
                            @foreach([1, 3, 7, 30, 90, 365] as $day)
                                <button type="button" data-add-days="{{ $day }}">+{{ $day }}天</button>
                            @endforeach
                        </div>
                    </form>
                </section>

                <section class="quick-user-actions">
                    <h3>{{ $isCardUser ? '充值卡快捷操作' : '账号快捷操作' }}</h3>
                    <div>
                        @if($isCardUser)
                            <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                                @csrf
                                <input type="hidden" name="card_id" value="{{ $card->id }}">
                                <input type="hidden" name="action" value="unbind_user">
                                <button type="submit">解除设备绑定</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                                @csrf
                                <input type="hidden" name="card_id" value="{{ $card->id }}">
                                <input type="hidden" name="action" value="unbind_user">
                                <button type="submit">解除设备绑定</button>
                            </form>
                            <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                                @csrf
                                <input type="hidden" name="card_id" value="{{ $card->id }}">
                                <input type="hidden" name="action" value="freeze_user">
                                <button type="submit">冻结账号</button>
                            </form>
                            <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                                @csrf
                                <input type="hidden" name="card_id" value="{{ $card->id }}">
                                <input type="hidden" name="action" value="unfreeze_user">
                                <button type="submit">恢复账号</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif

            <section class="quick-danger-zone">
                <div>
                    <strong>删除操作</strong>
                    <p>
                        @if($isAccountUser)
                            删除账号后将停止登录；已充值使用的卡密作为历史记录保留。
                        @else
                            未激活卡会直接删除；已激活卡会保留历史并标记为作废。
                        @endif
                    </p>
                </div>
                <div class="quick-danger-actions">
                    @if($isAccountUser)
                        <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                            @csrf
                            <input type="hidden" name="card_id" value="{{ $card->id }}">
                            <input type="hidden" name="action" value="delete_user">
                            <label><input type="checkbox" name="confirmed" value="1" required>确认删除账号</label>
                            <button type="submit">删除账号</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('admin.quick-operations.action', ['adminPath' => $adminPath]) }}">
                            @csrf
                            <input type="hidden" name="card_id" value="{{ $card->id }}">
                            <input type="hidden" name="action" value="delete_card">
                            <label><input type="checkbox" name="confirmed" value="1" required>确认删除卡密</label>
                            <button type="submit">{{ $isCardUser ? '删除/作废充值卡' : '删除未使用卡' }}</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    @else
        <div class="quick-operation-empty">
            <b>⌕</b>
            <strong>等待输入充值卡号</strong>
            <p>可查询代理发出的卡密，也可通过已充值卡直接找到对应用户名。</p>
        </div>
    @endif
</section>
@endsection
