@php
    $stepLabels = [
        'requirements' => '环境检测',
        'database' => '连接数据库',
        'site' => '站点设置',
        'administrator' => '创建管理员',
        'complete' => '安装完成',
    ];
    $currentIndex = array_search($step, $steps, true);
    $currentNumber = is_int($currentIndex) ? $currentIndex + 1 : 1;
    $database = is_array($database) ? $database : [];
    $site = is_array($site) ? $site : [];
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3867e8">
    <title>{{ $stepLabels[$step] }} · 安装 MyBsPHP2</title>
    <link rel="stylesheet" href="/assets/css/mybsphp.css?v=20260725-toast-v2">
    <script src="/assets/js/mybsphp.js?v=20260725-toast-v2" defer></script>
</head>
<body class="install-page">
@include('partials.toasts')
<a class="skip-link" href="#install-content">跳到安装内容</a>
<main class="install-shell">
    <section class="install-card" aria-labelledby="install-title">
        <header class="install-topbar">
            <a class="brand" href="{{ route('install.show') }}">
                <span class="brand-mark">BS</span>
                <span><strong>MyBsPHP2</strong><small>开源授权管理系统</small></span>
            </a>
            <span class="step-counter">步骤 {{ $currentNumber }} / 5</span>
        </header>

        <nav class="install-steps" aria-label="安装步骤">
            <ol>
                @foreach ($steps as $index => $stepKey)
                    @php
                        $number = $index + 1;
                        $state = $number < $currentNumber ? 'done' : ($number === $currentNumber ? 'current' : 'todo');
                    @endphp
                    <li class="is-{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                        <span>{{ $state === 'done' ? '✓' : $number }}</span>
                        <strong>{{ $stepLabels[$stepKey] }}</strong>
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="install-content" id="install-content">
            <header class="section-heading">
                <p class="eyebrow">MYBSPHP2 INSTALLER</p>
                <h1 id="install-title">{{ $stepLabels[$step] }}</h1>
                @if ($step === 'requirements')
                    <p>检查 PHP、必需扩展和目录权限，通过必要项目后继续。</p>
                @elseif ($step === 'database')
                    <p>填写 MySQL 连接信息。密码只用于本次安装，不会显示在页面中。</p>
                @elseif ($step === 'site')
                    <p>配置站点名称、时区和后台入口。后台入口默认是 admin，也可以安装时重写。</p>
                @elseif ($step === 'administrator')
                    <p>创建首个超级管理员。账号默认 admin，主密码最少 6 位，二级密码按需启用。</p>
                @else
                    <p>站点、数据库与管理员已经配置完成，请先保存后台入口。</p>
                @endif
            </header>

            @if ($step === 'requirements')
                <ul class="requirement-list">
                    @foreach ($checks as $check)
                        <li class="{{ $check['ok'] ? 'is-success' : ($check['required'] ? 'is-danger' : 'is-warning') }}">
                            <span class="requirement-icon" aria-hidden="true">{{ $check['ok'] ? '✓' : ($check['required'] ? '×' : '!') }}</span>
                            <span>
                                <strong>{{ $check['label'] }}</strong>
                                <small>{{ $check['detail'] }}</small>
                            </span>
                            <em>{{ $check['ok'] ? '通过' : ($check['required'] ? '未通过' : '建议') }}</em>
                        </li>
                    @endforeach
                </ul>

                <form method="post" action="{{ route('install.requirements') }}" data-submit-form>
                    @csrf
                    <div class="form-actions">
                        <a class="button button-secondary" href="{{ route('install.show') }}">重新检测</a>
                        <button class="button button-primary" type="submit" @disabled($hasBlockingRequirement)>
                            下一步 <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            @elseif ($step === 'database')
                <form method="post" action="{{ route('install.database') }}" class="stack-form" data-submit-form>
                    @csrf
                    <div class="field-grid field-grid-host">
                        <label class="field">
                            <span>数据库地址</span>
                            <input name="host" value="{{ old('host', $database['host'] ?? '127.0.0.1') }}"
                                   required autocomplete="off">
                            <small>本机 MySQL 通常为 127.0.0.1</small>
                        </label>
                        <label class="field">
                            <span>端口</span>
                            <input name="port" type="number" min="1" max="65535"
                                   value="{{ old('port', $database['port'] ?? 3306) }}" required>
                        </label>
                    </div>
                    <label class="field">
                        <span>数据库名称</span>
                        <input name="database" value="{{ old('database', $database['database'] ?? 'mybsphp') }}"
                               pattern="[A-Za-z0-9_]{1,64}" maxlength="64" required autocomplete="off">
                    </label>
                    <div class="field-grid">
                        <label class="field">
                            <span>数据库用户名</span>
                            <input name="username" value="{{ old('username', $database['username'] ?? 'root') }}"
                                   required autocomplete="username">
                        </label>
                        <label class="field">
                            <span>数据库密码 <small>可为空</small></span>
                            <span class="password-input">
                                <input name="password" id="db-password" type="password" autocomplete="new-password">
                                <button type="button" data-password-toggle="db-password" aria-pressed="false">显示</button>
                            </span>
                        </label>
                    </div>
                    <label class="choice-row">
                        <input name="create_database" type="checkbox" value="1"
                               @checked(old('create_database', $database['create_database'] ?? true))>
                        <span><strong>数据库不存在时自动创建</strong><small>数据库账号需要具备创建权限。</small></span>
                    </label>
                    <fieldset class="existing-data-choices">
                        <legend>如果数据库已有数据</legend>
                        <label class="choice-row">
                            <input name="existing_data_action" type="radio" value="preserve_business"
                                   @checked(old('existing_data_action', $database['existing_data_action'] ?? 'preserve_business') === 'preserve_business')>
                            <span>
                                <strong>保留应用和卡密 <em>默认</em></strong>
                                <small>保留应用、卡种、卡密、批次和发卡记录，只重置后台管理员、角色权限与系统日志。</small>
                            </span>
                        </label>
                        <label class="choice-row choice-danger">
                            <input name="existing_data_action" type="radio" value="reset_all"
                                   @checked(old('existing_data_action', $database['existing_data_action'] ?? '') === 'reset_all')>
                            <span><strong>清空全部数据</strong><small>删除整个数据库并重新创建，应用和卡密也会一起清除。</small></span>
                        </label>
                    </fieldset>
                    <div class="form-actions">
                        <a class="button button-secondary" href="{{ route('install.show') }}">上一步</a>
                        <button class="button button-primary" type="submit">测试连接并继续 <span>→</span></button>
                    </div>
                </form>
            @elseif ($step === 'site')
                <form method="post" action="{{ route('install.site') }}" class="stack-form" data-submit-form>
                    @csrf
                    <label class="field">
                        <span>站点名称</span>
                        <input name="app_name" maxlength="80"
                               value="{{ old('app_name', $site['app_name'] ?? 'MyBsPHP2') }}" required>
                    </label>
                    <label class="field">
                        <span>站点地址</span>
                        <input name="app_url" type="url"
                               value="{{ old('app_url', $site['app_url'] ?? url('/')) }}" required>
                        <small>填写完整地址，保存时会移除末尾斜杠。</small>
                    </label>
                    <label class="field">
                        <span>时区</span>
                        <select name="timezone" required>
                            @foreach ($timezones as $timezone)
                                <option value="{{ $timezone }}"
                                    @selected(old('timezone', $site['timezone'] ?? 'Asia/Shanghai') === $timezone)>
                                    {{ $timezone }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>后台入口</span>
                        <span class="path-input">
                            <b aria-hidden="true">/</b>
                            <input name="admin_path" id="admin-path"
                                   value="{{ old('admin_path', $site['admin_path'] ?? 'admin') }}"
                                   pattern="[A-Za-z0-9_-]{3,48}" maxlength="48" required>
                        </span>
                        <small>3～48 位字母、数字、短横线或下划线。默认 admin，安装后旧地址返回 404。</small>
                    </label>
                    <aside class="info-panel">
                        <span aria-hidden="true">◈</span>
                        <div><strong>请保存最终后台入口</strong><p>入口用于减少无目的扫描，同时继续使用高强度密码和登录限流。</p></div>
                    </aside>
                    <div class="form-actions">
                        <a class="button button-secondary" href="{{ route('install.show', ['step' => 'database']) }}">上一步</a>
                        <button class="button button-primary" type="submit">保存站点设置 <span>→</span></button>
                    </div>
                </form>
            @elseif ($step === 'administrator')
                <form method="post" action="{{ route('install.complete') }}" class="stack-form" data-submit-form>
                    @csrf
                    <label class="field">
                        <span>管理员账号</span>
                        <input name="username" value="{{ old('username', 'admin') }}"
                               pattern="[A-Za-z0-9_.-]{3,32}" minlength="3" maxlength="32"
                               autocomplete="username" required autofocus>
                    </label>
                    <label class="field">
                        <span>管理员密码</span>
                        <span class="password-input">
                            <input name="password" id="admin-password" type="password"
                                   minlength="6" maxlength="128" autocomplete="new-password" required>
                            <button type="button" data-password-toggle="admin-password" aria-pressed="false">显示</button>
                        </span>
                        <small>最少 6 位，建议使用 12 位以上字母、数字和符号组合。</small>
                    </label>
                    <label class="field">
                        <span>确认管理员密码</span>
                        <span class="password-input">
                            <input name="password_confirmation" id="admin-password-confirmation" type="password"
                                   minlength="6" maxlength="128" autocomplete="new-password" required>
                            <button type="button" data-password-toggle="admin-password-confirmation" aria-pressed="false">显示</button>
                        </span>
                    </label>
                    <label class="choice-row">
                        <input name="secondary_password_enabled" type="checkbox" value="1"
                               data-secondary-toggle @checked(old('secondary_password_enabled'))>
                        <span><strong>启用管理员二级密码</strong><small>默认关闭；开启后登录需要额外口令。</small></span>
                    </label>
                    <div class="secondary-fields" data-secondary-fields hidden>
                        <label class="field">
                            <span>二级密码</span>
                            <span class="password-input">
                                <input name="secondary_password" id="secondary-password" type="password"
                                       minlength="6" maxlength="128" autocomplete="new-password" disabled>
                                <button type="button" data-password-toggle="secondary-password" aria-pressed="false">显示</button>
                            </span>
                        </label>
                        <label class="field">
                            <span>确认二级密码</span>
                            <span class="password-input">
                                <input name="secondary_password_confirmation" id="secondary-password-confirmation"
                                       type="password" minlength="6" maxlength="128"
                                       autocomplete="new-password" disabled>
                                <button type="button" data-password-toggle="secondary-password-confirmation" aria-pressed="false">显示</button>
                            </span>
                        </label>
                    </div>

                    @if (($database['table_count'] ?? 0) > 0 && ($database['existing_data_action'] ?? 'preserve_business') === 'preserve_business')
                        <aside class="info-panel preserve-data-panel">
                            <span aria-hidden="true">✓</span>
                            <div>
                                <strong>默认保留业务数据</strong>
                                <p>当前应用、卡种、卡密、批次和发卡记录保持不变；安装完成后使用新创建的超级管理员登录。</p>
                            </div>
                        </aside>
                    @endif

                    <label class="choice-row">
                        <input name="confirm_install" type="checkbox" value="1" required>
                        <span><strong>我已保存数据库信息和后台入口，并确认开始安装</strong></span>
                    </label>
                    <div class="form-actions">
                        <a class="button button-secondary" href="{{ route('install.show', ['step' => 'site']) }}">上一步</a>
                        <button class="button button-primary" type="submit">
                            {{ (($database['existing_data_action'] ?? '') === 'reset_all') ? '确认清空并安装' : '保留业务数据并安装' }}
                            <span>✓</span>
                        </button>
                    </div>
                </form>
            @else
                <section class="success-panel">
                    <span class="success-icon" aria-hidden="true">✓</span>
                    <h2>安装成功</h2>
                    <p>数据库、站点和超级管理员已经配置完成。</p>
                    <dl>
                        <div><dt>站点名称</dt><dd>{{ $summary['app_name'] }}</dd></div>
                        <div><dt>后台入口</dt><dd><code>/{{ $summary['admin_path'] }}</code></dd></div>
                        <div><dt>管理员账号</dt><dd>{{ $summary['username'] }}</dd></div>
                        <div><dt>二级密码</dt><dd>{{ $summary['secondary_password_enabled'] ? '已启用' : '关闭' }}</dd></div>
                    </dl>
                    <div class="success-actions">
                        <button class="button button-secondary" type="button"
                                data-copy="{{ url('/'.$summary['admin_path']) }}">复制后台入口</button>
                        <a class="button button-primary" href="{{ url('/'.$summary['admin_path']) }}">进入管理后台 →</a>
                    </div>
                </section>
            @endif
        </div>

        <footer class="install-footer">
            <span>MyBsPHP2</span><span>PHP 8.3 + Laravel 13 + MySQL</span>
        </footer>
    </section>
</main>
</body>
</html>
