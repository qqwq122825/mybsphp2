<?php
declare(strict_types=1);

/**
 * MyBsPHP installation wizard.
 *
 * Expected variables:
 * @var int|string $step
 * @var array<int|string, mixed> $errors
 * @var array<string, mixed> $values
 * @var array<int|string, mixed> $checks
 * @var string $csrfToken
 * @var bool $reinstallAuthorized
 */

$step = $step ?? 1;
$errors = is_array($errors ?? null) ? $errors : [];
$values = is_array($values ?? null) ? $values : [];
$checks = is_array($checks ?? null) ? $checks : [];
$csrfToken = (string) ($csrfToken ?? '');
$reinstallAuthorized = (bool) ($reinstallAuthorized ?? false);

$stepAliases = [
    'environment' => 1,
    'requirements' => 1,
    'checks' => 1,
    'database' => 2,
    'db' => 2,
    'site' => 3,
    'settings' => 3,
    'admin' => 4,
    'administrator' => 4,
    'complete' => 5,
    'finished' => 5,
];
$currentStep = is_numeric($step)
    ? max(1, min(5, (int) $step))
    : ($stepAliases[strtolower((string) $step)] ?? 1);

$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
$old = static function (string $key, mixed $default = '') use ($values): mixed {
    if (array_key_exists($key, $values)) {
        return $values[$key];
    }

    $upperKey = strtoupper($key);
    return $values[$upperKey] ?? $default;
};
$fieldError = static function (string $field) use ($errors): string {
    $message = $errors[$field] ?? $errors[strtoupper($field)] ?? '';
    if (is_array($message)) {
        $message = reset($message) ?: '';
    }
    return is_scalar($message) ? (string) $message : '';
};
$errorMessages = [];
foreach ($errors as $error) {
    if (is_array($error)) {
        foreach ($error as $nestedError) {
            if (is_scalar($nestedError)) {
                $errorMessages[] = (string) $nestedError;
            }
        }
    } elseif (is_scalar($error)) {
        $errorMessages[] = (string) $error;
    }
}
$errorMessages = array_values(array_unique(array_filter($errorMessages)));

$steps = [
    1 => ['title' => '环境检测', 'short' => '环境'],
    2 => ['title' => '连接数据库', 'short' => '数据库'],
    3 => ['title' => '站点设置', 'short' => '站点'],
    4 => ['title' => '创建管理员', 'short' => '管理员'],
    5 => ['title' => '安装完成', 'short' => '完成'],
];

$normalizedChecks = [];
foreach ($checks as $key => $check) {
    if (is_array($check)) {
        $normalizedChecks[] = [
            'label' => (string) ($check['label'] ?? $check['name'] ?? $key),
            'ok' => (bool) ($check['ok'] ?? $check['passed'] ?? $check['status'] ?? false),
            'detail' => (string) ($check['detail'] ?? $check['message'] ?? $check['value'] ?? ''),
            'required' => (bool) ($check['required'] ?? true),
        ];
    } else {
        $normalizedChecks[] = [
            'label' => is_string($key) ? $key : '环境项目',
            'ok' => (bool) $check,
            'detail' => '',
            'required' => true,
        ];
    }
}
$hasBlockingCheck = false;
foreach ($normalizedChecks as $check) {
    if ($check['required'] && !$check['ok']) {
        $hasBlockingCheck = true;
        break;
    }
}

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/install'), PHP_URL_PATH);
$formAction = is_string($requestPath) && $requestPath !== '' ? $requestPath : '/install';
$adminPath = trim((string) $old('admin_path', 'admin'), '/');
$adminPath = $adminPath !== '' ? $adminPath : 'admin';
$adminUrl = '/' . rawurlencode($adminPath);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#3157d5">
    <title><?= $escape($steps[$currentStep]['title']) ?> · 安装 MyBsPHP</title>
    <link rel="stylesheet" href="/assets/css/install.css">
    <script src="/assets/js/install.js" defer></script>
</head>
<body>
<a class="skip-link" href="#install-main">跳到安装内容</a>

<main class="install-shell" id="install-main">
    <section class="install-panel" aria-labelledby="install-title">
        <header class="install-header">
            <a class="brand" href="/install" aria-label="MyBsPHP 安装向导首页">
                <span class="brand__mark" aria-hidden="true">B</span>
                <span>
                    <strong>MyBsPHP</strong>
                    <small>开源授权管理系统</small>
                </span>
            </a>
            <span class="step-count" aria-label="当前安装进度">步骤 <?= $currentStep ?> / 5</span>
        </header>

        <?php if ($reinstallAuthorized): ?>
            <div class="reinstall-banner" role="status">
                <strong>重装维护模式已授权</strong>
                <span>令牌仅本次 Session 有效；覆盖数据库仍需在最后一步再次确认。</span>
            </div>
        <?php endif; ?>

        <nav class="stepper" aria-label="安装步骤">
            <ol>
                <?php foreach ($steps as $number => $stepInfo): ?>
                    <?php
                    $state = $number < $currentStep ? 'complete' : ($number === $currentStep ? 'current' : 'upcoming');
                    ?>
                    <li class="stepper__item is-<?= $state ?>"<?= $number === $currentStep ? ' aria-current="step"' : '' ?>>
                        <span class="stepper__number" aria-hidden="true">
                            <?= $number < $currentStep ? '✓' : $number ?>
                        </span>
                        <span class="stepper__text">
                            <small>步骤 <?= $number ?></small>
                            <strong><?= $escape($stepInfo['short']) ?></strong>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <div class="install-content">
            <div class="section-heading">
                <p class="eyebrow">安装向导</p>
                <h1 id="install-title"><?= $escape($steps[$currentStep]['title']) ?></h1>
                <?php if ($currentStep === 1): ?>
                    <p>开始前检查服务器是否满足运行要求。通过全部必要项目后即可继续。</p>
                <?php elseif ($currentStep === 2): ?>
                    <p>填写 MySQL 连接信息。安装程序将测试连接并创建所需数据表。</p>
                <?php elseif ($currentStep === 3): ?>
                    <p>配置站点名称和后台入口。修改默认入口可以减少后台地址被扫描的机会。</p>
                <?php elseif ($currentStep === 4): ?>
                    <p>创建首个超级管理员。系统仅使用用户名和密码，不要求邮箱或验证码。</p>
                <?php else: ?>
                    <p>基础配置已经写入，MyBsPHP 现在可以使用。</p>
                <?php endif; ?>
            </div>

            <?php if ($errorMessages !== []): ?>
                <div class="alert alert--error" role="alert" aria-labelledby="error-title" tabindex="-1" data-error-summary>
                    <span class="alert__icon" aria-hidden="true">!</span>
                    <div>
                        <strong id="error-title">请检查以下内容</strong>
                        <ul>
                            <?php foreach ($errorMessages as $message): ?>
                                <li><?= $escape($message) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($currentStep === 1): ?>
                <?php if ($normalizedChecks === []): ?>
                    <div class="empty-state">
                        <span aria-hidden="true">⌁</span>
                        <strong>正在等待环境检测结果</strong>
                        <p>刷新检测后，这里将显示 PHP、扩展和目录权限状态。</p>
                    </div>
                <?php else: ?>
                    <ul class="check-list" aria-label="服务器环境检测结果">
                        <?php foreach ($normalizedChecks as $check): ?>
                            <li class="check-item <?= $check['ok'] ? 'is-ok' : 'is-failed' ?>">
                                <span class="check-item__state" aria-hidden="true"><?= $check['ok'] ? '✓' : '×' ?></span>
                                <span class="check-item__content">
                                    <strong><?= $escape($check['label']) ?></strong>
                                    <?php if ($check['detail'] !== ''): ?>
                                        <small><?= $escape($check['detail']) ?></small>
                                    <?php endif; ?>
                                </span>
                                <span class="status-badge"><?= $check['ok'] ? '通过' : ($check['required'] ? '未通过' : '建议') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post" action="<?= $escape($formAction) ?>" class="install-form" data-install-form>
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="step" value="environment">
                    <div class="form-actions">
                        <button class="button button--secondary" type="submit" name="action" value="recheck">
                            重新检测
                        </button>
                        <button class="button button--primary" type="submit" name="action" value="next"<?= $hasBlockingCheck ? ' disabled aria-disabled="true"' : '' ?>>
                            下一步
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                    <?php if ($hasBlockingCheck): ?>
                        <p class="form-note form-note--warning">必要项目尚未通过，请修复后重新检测。</p>
                    <?php endif; ?>
                </form>

            <?php elseif ($currentStep === 2): ?>
                <form method="post" action="<?= $escape($formAction) ?>" class="install-form" data-install-form>
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="step" value="database">

                    <div class="field-grid field-grid--split">
                        <div class="field">
                            <label for="db_host">数据库地址</label>
                            <input id="db_host" name="db_host" type="text" value="<?= $escape($old('db_host', '127.0.0.1')) ?>"
                                   inputmode="url" autocomplete="off" required aria-describedby="db_host_help<?= $fieldError('db_host') !== '' ? ' db_host_error' : '' ?>"
                                   <?= $fieldError('db_host') !== '' ? 'aria-invalid="true"' : '' ?>>
                            <small id="db_host_help">本机 MySQL 通常为 127.0.0.1</small>
                            <?php if ($fieldError('db_host') !== ''): ?>
                                <small class="field-error" id="db_host_error"><?= $escape($fieldError('db_host')) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="field field--compact">
                            <label for="db_port">端口</label>
                            <input id="db_port" name="db_port" type="number" value="<?= $escape($old('db_port', '3306')) ?>"
                                   inputmode="numeric" min="1" max="65535" autocomplete="off" required
                                   <?= $fieldError('db_port') !== '' ? 'aria-invalid="true" aria-describedby="db_port_error"' : '' ?>>
                            <?php if ($fieldError('db_port') !== ''): ?>
                                <small class="field-error" id="db_port_error"><?= $escape($fieldError('db_port')) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="db_database">数据库名称</label>
                        <input id="db_database" name="db_database" type="text" value="<?= $escape($old('db_database', 'mybsphp')) ?>"
                               autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="64" required
                               pattern="[A-Za-z0-9_$-]+" data-slug-input
                               <?= $fieldError('db_database') !== '' ? 'aria-invalid="true" aria-describedby="db_database_error"' : '' ?>>
                        <?php if ($fieldError('db_database') !== ''): ?>
                            <small class="field-error" id="db_database_error"><?= $escape($fieldError('db_database')) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label for="db_username">数据库用户名</label>
                            <input id="db_username" name="db_username" type="text" value="<?= $escape($old('db_username', 'root')) ?>"
                                   autocomplete="username" autocapitalize="none" spellcheck="false" required
                                   <?= $fieldError('db_username') !== '' ? 'aria-invalid="true" aria-describedby="db_username_error"' : '' ?>>
                            <?php if ($fieldError('db_username') !== ''): ?>
                                <small class="field-error" id="db_username_error"><?= $escape($fieldError('db_username')) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="db_password">数据库密码 <span class="label-hint">可为空</span></label>
                            <div class="password-field">
                                <input id="db_password" name="db_password" type="password" value=""
                                       autocomplete="new-password" aria-describedby="db_password_help"
                                       <?= $fieldError('db_password') !== '' ? 'aria-invalid="true"' : '' ?>>
                                <button type="button" class="password-toggle" data-password-toggle="db_password" aria-label="显示数据库密码" aria-pressed="false">
                                    <span aria-hidden="true">显示</span>
                                </button>
                            </div>
                            <small id="db_password_help">留空表示数据库账号没有密码</small>
                            <?php if ($fieldError('db_password') !== ''): ?>
                                <small class="field-error"><?= $escape($fieldError('db_password')) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <label class="confirmation">
                        <input name="create_database" type="checkbox" value="1"
                               <?= (bool) $old('create_database', true) ? 'checked' : '' ?>>
                        <span>数据库不存在时自动创建</span>
                    </label>

                    <label class="confirmation confirmation--danger">
                        <input name="overwrite_database" type="checkbox" value="1"
                               <?= (bool) $old('overwrite_database', false) ? 'checked' : '' ?>>
                        <span>
                            <strong>覆盖现有数据库</strong>
                            <small>将删除所选数据库中的全部数据表，再重新创建 MyBsPHP。</small>
                        </span>
                    </label>

                    <div class="form-actions">
                        <button class="button button--primary button--full" type="submit" name="action" value="next">
                            测试连接并继续
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($currentStep === 3): ?>
                <form method="post" action="<?= $escape($formAction) ?>" class="install-form" data-install-form>
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="step" value="site">

                    <div class="field">
                        <label for="app_name">站点名称</label>
                        <input id="app_name" name="app_name" type="text" value="<?= $escape($old('app_name', 'MyBsPHP')) ?>"
                               maxlength="60" autocomplete="organization" required
                               <?= $fieldError('app_name') !== '' ? 'aria-invalid="true" aria-describedby="app_name_error"' : '' ?>>
                        <?php if ($fieldError('app_name') !== ''): ?>
                            <small class="field-error" id="app_name_error"><?= $escape($fieldError('app_name')) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="app_url">站点地址</label>
                        <input id="app_url" name="app_url" type="url" value="<?= $escape($old('app_url', '')) ?>"
                               inputmode="url" autocomplete="url" placeholder="https://license.example.com" required
                               <?= $fieldError('app_url') !== '' ? 'aria-invalid="true" aria-describedby="app_url_help app_url_error"' : 'aria-describedby="app_url_help"' ?>>
                        <small id="app_url_help">填写完整地址，不要在末尾添加斜杠。</small>
                        <?php if ($fieldError('app_url') !== ''): ?>
                            <small class="field-error" id="app_url_error"><?= $escape($fieldError('app_url')) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="admin_path">后台入口</label>
                        <div class="path-field">
                            <span aria-hidden="true">/</span>
                            <input id="admin_path" name="admin_path" type="text" value="<?= $escape($old('admin_path', 'admin')) ?>"
                                   maxlength="48" autocomplete="off" autocapitalize="none" spellcheck="false" required
                                   pattern="[A-Za-z0-9_-]{3,48}" data-admin-path
                                   aria-describedby="admin_path_help<?= $fieldError('admin_path') !== '' ? ' admin_path_error' : '' ?>"
                                   <?= $fieldError('admin_path') !== '' ? 'aria-invalid="true"' : '' ?>>
                        </div>
                        <small id="admin_path_help">默认是 admin。建议安装时换成仅自己知道的 3–48 位字母、数字、短横线或下划线。</small>
                        <?php if ($fieldError('admin_path') !== ''): ?>
                            <small class="field-error" id="admin_path_error"><?= $escape($fieldError('admin_path')) ?></small>
                        <?php endif; ?>
                    </div>

                    <aside class="security-tip" aria-label="后台入口提示">
                        <span aria-hidden="true">◈</span>
                        <div>
                            <strong>请保存后台入口</strong>
                            <p>安装完成后，需要通过自定义入口访问后台。入口不是密码，管理员仍需使用高强度密码。</p>
                        </div>
                    </aside>

                    <div class="form-actions">
                        <button class="button button--primary button--full" type="submit" name="action" value="next">
                            保存站点设置
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($currentStep === 4): ?>
                <form method="post" action="<?= $escape($formAction) ?>" class="install-form" data-install-form>
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="step" value="admin">

                    <div class="field">
                        <label for="admin_username">管理员用户名</label>
                        <input id="admin_username" name="admin_username" type="text" value="<?= $escape($old('admin_username', 'admin')) ?>"
                               minlength="3" maxlength="32" autocomplete="username" autocapitalize="none" spellcheck="false"
                               pattern="[A-Za-z0-9_.-]{3,32}" required autofocus
                               aria-describedby="admin_username_help<?= $fieldError('admin_username') !== '' ? ' admin_username_error' : '' ?>"
                               <?= $fieldError('admin_username') !== '' ? 'aria-invalid="true"' : '' ?>>
                        <small id="admin_username_help">使用 3–32 位字母、数字、点、短横线或下划线。</small>
                        <?php if ($fieldError('admin_username') !== ''): ?>
                            <small class="field-error" id="admin_username_error"><?= $escape($fieldError('admin_username')) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="admin_password">管理员密码</label>
                        <div class="password-field">
                            <input id="admin_password" name="admin_password" type="password"
                                   minlength="6" maxlength="128" autocomplete="new-password" required
                                   aria-describedby="password_help password_strength<?= $fieldError('admin_password') !== '' ? ' admin_password_error' : '' ?>"
                                   data-password-strength
                                   <?= $fieldError('admin_password') !== '' ? 'aria-invalid="true"' : '' ?>>
                            <button type="button" class="password-toggle" data-password-toggle="admin_password" aria-label="显示管理员密码" aria-pressed="false">
                                <span aria-hidden="true">显示</span>
                            </button>
                        </div>
                        <div class="password-meter" aria-hidden="true">
                            <span data-password-meter></span>
                        </div>
                        <small id="password_help">至少 6 位，建议组合大小写字母、数字和符号。</small>
                        <small id="password_strength" class="password-strength" aria-live="polite"></small>
                        <?php if ($fieldError('admin_password') !== ''): ?>
                            <small class="field-error" id="admin_password_error"><?= $escape($fieldError('admin_password')) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="admin_password_confirmation">确认管理员密码</label>
                        <div class="password-field">
                            <input id="admin_password_confirmation" name="admin_password_confirmation" type="password"
                                   minlength="6" maxlength="128" autocomplete="new-password" required
                                   data-password-confirm="admin_password"
                                   <?= $fieldError('admin_password_confirmation') !== '' ? 'aria-invalid="true" aria-describedby="admin_password_confirmation_error"' : '' ?>>
                            <button type="button" class="password-toggle" data-password-toggle="admin_password_confirmation" aria-label="显示确认密码" aria-pressed="false">
                                <span aria-hidden="true">显示</span>
                            </button>
                        </div>
                        <?php if ($fieldError('admin_password_confirmation') !== ''): ?>
                            <small class="field-error" id="admin_password_confirmation_error"><?= $escape($fieldError('admin_password_confirmation')) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="admin_secondary_password">二级密码</label>
                        <div class="password-field">
                            <input id="admin_secondary_password" name="admin_secondary_password" type="password"
                                   minlength="6" maxlength="128" autocomplete="new-password" required
                                   aria-describedby="secondary_password_help"
                                   <?= $fieldError('admin_secondary_password') !== '' ? 'aria-invalid="true"' : '' ?>>
                            <button type="button" class="password-toggle" data-password-toggle="admin_secondary_password" aria-label="显示二级密码" aria-pressed="false">
                                <span aria-hidden="true">显示</span>
                            </button>
                        </div>
                        <small id="secondary_password_help">至少 6 位；登录后台时需要同时填写。</small>
                    </div>

                    <div class="field">
                        <label for="admin_secondary_password_confirmation">确认二级密码</label>
                        <div class="password-field">
                            <input id="admin_secondary_password_confirmation" name="admin_secondary_password_confirmation" type="password"
                                   minlength="6" maxlength="128" autocomplete="new-password" required
                                   data-password-confirm="admin_secondary_password"
                                   <?= $fieldError('admin_secondary_password_confirmation') !== '' ? 'aria-invalid="true"' : '' ?>>
                            <button type="button" class="password-toggle" data-password-toggle="admin_secondary_password_confirmation" aria-label="显示确认二级密码" aria-pressed="false">
                                <span aria-hidden="true">显示</span>
                            </button>
                        </div>
                    </div>

                    <?php if ((bool) $old('overwrite_database', false)): ?>
                        <section class="overwrite-confirmation" aria-labelledby="overwrite-title">
                            <h2 id="overwrite-title">覆盖安装最终确认</h2>
                            <p>
                                数据库 <code><?= $escape($old('db_database', 'mybsphp')) ?></code>
                                中的现有表和管理员数据会被清除。
                            </p>
                            <div class="field">
                                <label for="overwrite_database_name">
                                    输入数据库名 <strong><?= $escape($old('db_database', 'mybsphp')) ?></strong>
                                </label>
                                <input id="overwrite_database_name" name="overwrite_database_name" type="text"
                                       autocomplete="off" required
                                       pattern="<?= $escape((string) $old('db_database', 'mybsphp')) ?>"
                                       placeholder="<?= $escape($old('db_database', 'mybsphp')) ?>">
                            </div>
                            <label class="confirmation confirmation--danger">
                                <input name="confirm_overwrite" type="checkbox" value="1" required>
                                <span>我确认清除这个数据库并重新安装。</span>
                            </label>
                        </section>
                    <?php endif; ?>

                    <label class="confirmation">
                        <input name="confirm_install" type="checkbox" value="1" required>
                        <span>我已保存数据库信息和后台入口，并确认开始安装。</span>
                    </label>

                    <div class="form-actions">
                        <button class="button button--primary button--full" type="submit" name="action" value="install">
                            完成安装
                            <span aria-hidden="true">✓</span>
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="success-card">
                    <span class="success-card__icon" aria-hidden="true">✓</span>
                    <h2>安装成功</h2>
                    <p>数据库、站点和管理员信息已配置完成。请妥善保管后台入口。</p>
                    <dl>
                        <div>
                            <dt>站点名称</dt>
                            <dd><?= $escape($old('app_name', 'MyBsPHP')) ?></dd>
                        </div>
                        <div>
                            <dt>后台入口</dt>
                            <dd><code><?= $escape($adminUrl) ?></code></dd>
                        </div>
                    </dl>
                    <a class="button button--primary button--full" href="<?= $escape($adminUrl) ?>">
                        进入管理后台
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
                <p class="form-note">出于安全考虑，安装程序已锁定。需要重新安装时，请先按部署文档执行重置流程。</p>
            <?php endif; ?>
        </div>

        <footer class="install-footer">
            <span>MyBsPHP</span>
            <span aria-hidden="true">·</span>
            <span>PHP + MySQL</span>
        </footer>
    </section>
</main>
</body>
</html>
