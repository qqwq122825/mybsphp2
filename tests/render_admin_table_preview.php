<?php

use Illuminate\Contracts\Console\Kernel;
use Tests\Support\AdminTablePreview;

// Local, read-only HTML preview from the same Blade views as the application.
// Run with DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php tests/render_admin_table_preview.php
require dirname(__DIR__).'/vendor/autoload.php';

if (getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== ':memory:') {
    fwrite(STDERR, "Use the in-memory SQLite environment for presentation previews.\n");
    exit(1);
}
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$output = storage_path('app/admin-table-preview');
@mkdir($output, 0755, true);
if (! file_exists($output.'/assets')) {
    symlink(public_path('assets'), $output.'/assets');
}
if (! file_exists($output.'/build')) {
    symlink(public_path('build'), $output.'/build');
}
$pages = ['agent-dashboard' => '代理端', 'agent-cards' => '代理卡管理', 'agent-purchase' => '代理制卡', 'agent-finance' => '代理财务', 'agent-login' => '代理登录', 'agent-purchase-result' => '制卡结果', 'agent-cards-empty' => '代理空状态', 'agent-delete' => '退款确认', 'agent-unbind' => '解绑确认', 'agent-login-error' => '登录错误', 'agent-purchase-low' => '余额不足', 'agent-purchase-empty' => '无可用卡种', 'agent-finance-empty' => '无流水', 'software' => '软件管理', 'software-config' => '软件配置', 'software-users' => '软件用户', 'software-create' => '添加软件', 'software-account-config' => '账户配置', 'software-account-users' => '账户用户', 'center' => '封控中心', 'blocklist' => '黑名单请求', 'simple' => '客户端请求', 'detailed' => '详细日志', 'protection' => '安全日志', 'administrators' => '管理员', 'empty' => '空状态'];
$styles = '<style>.preview-strip{display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:12px 16px;margin-bottom:16px;border:1px solid #dce5f6;border-radius:10px;background:#f4f7ff;font:12px/1.5 system-ui;color:#667793}.preview-strip strong{color:#3d5c9f;margin-right:auto}.preview-strip a{padding:5px 9px;border-radius:6px;color:#56709c;text-decoration:none}.preview-strip a[aria-current]{background:#e3ebff;color:#315bcd}.preview-notice{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);padding:12px 20px;background:#243a60;color:white;border-radius:10px;z-index:99999;font-size:13px}.preview-disabled{opacity:.45}@media print{.preview-strip{display:none}}</style>';
$script = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const showNotice = (message) => {
        document.querySelector('.preview-notice')?.remove();
        const notice = document.createElement('div'); notice.className = 'preview-notice'; notice.textContent = message;
        document.body.append(notice); setTimeout(() => notice.remove(), 3000);
    };
    const prepareLink = a => {
        const url = new URL(a.href);
        const path = url.pathname;
        let page = path.endsWith('/risk-control/center') ? 'center' : path.endsWith('/administrators') ? 'administrators' : path.endsWith('/system-logs/blocklist-requests') ? 'blocklist' : path.endsWith('/system-logs/client-requests') ? (url.searchParams.get('view') === 'detailed' ? 'detailed' : 'simple') : path.endsWith('/system-logs/protection') ? 'protection' : null;
        if (/\/applications$/.test(path)) page = 'software';
        if (/\/applications\/create$/.test(path)) page = 'software-create';
        if (/\/applications\/[^/]+\/edit$/.test(path)) page = path.includes('preview-software-4') ? 'software-account-config' : 'software-config';
        if (/\/applications\/[^/]+\/users$/.test(path)) page = path.includes('preview-software-4') ? 'software-account-users' : 'software-users';
        if (page) a.href = '/' + page + '.html';
        else if (!path.endsWith('.html') && a.getAttribute('href') !== '#') {
            a.classList.add('preview-disabled');
            a.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); showNotice('此链接未包含在本次样式预览中'); });
        }
    };
    document.querySelectorAll('a[href]').forEach(prepareLink);
    new MutationObserver(changes => changes.forEach(change => change.addedNodes.forEach(node => {
        if (node.nodeType !== 1) return;
        if (node.matches('a[href]')) prepareLink(node);
        node.querySelectorAll('a[href]').forEach(prepareLink);
    }))).observe(document.body, { childList: true, subtree: true });
    document.addEventListener('submit', e => {
        if (e.target.closest('.ep-admin-list, .ep-admin-dialog, .ep-software-form, [data-agent-host]')) return;
        e.preventDefault();
        if (e.target.matches('.admin-filter-bar')) {
            const keyword = (new FormData(e.target).get('keyword') || '').toString().toLowerCase();
            const subject = new FormData(e.target).get('subject_type');
            const rows = [...document.querySelectorAll('.admin-data-table tbody tr')];
            rows.forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(keyword) || (subject && subject !== 'all' && row.querySelector('.admin-type-tag')?.textContent.trim() !== (subject === 'device' ? '设备' : 'IP')); });
            showNotice('已筛选当前页示例数据');
        } else showNotice('样式预览：操作已演示，业务数据保持原状');
    }, true);
});
</script>
JS;
foreach ($pages as $page => $title) {
    $html = AdminTablePreview::render($page);
    // Remap workspace bootstrap URLs before deferred application scripts run.
    // Mapping only anchors later would leave refresh at a nonexistent /admin URL.
    $html = preg_replace_callback('/\b(href|data-workspace-(?:base|bootstrap)-url)="([^"]+)"/', static function (array $match): string {
        $url = html_entity_decode($match[2]);
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $slug = match (true) {
            (bool) preg_match('~/agent/dashboard$~', $path) => 'agent-dashboard',
            (bool) preg_match('~/agent/login$~', $path) => 'agent-login',
            (bool) preg_match('~/agent/cards$~', $path) => 'agent-cards',
            (bool) preg_match('~/agent/purchase$~', $path) => 'agent-purchase',
            (bool) preg_match('~/agent/finance$~', $path) => 'agent-finance',
            (bool) preg_match('~/applications$~', $path) => 'software',
            (bool) preg_match('~/applications/create$~', $path) => 'software-create',
            (bool) preg_match('~/applications/[^/]+/edit$~', $path) => str_contains($path, 'preview-software-4') ? 'software-account-config' : 'software-config',
            (bool) preg_match('~/applications/[^/]+/users$~', $path) => str_contains($path, 'preview-software-4') ? 'software-account-users' : 'software-users',
            default => null,
        };

        return $slug ? $match[1].'="/'.$slug.'.html"' : $match[0];
    }, $html);
    $html = str_replace('<html lang="zh-CN">', '<html lang="zh-CN" data-admin-preview>', $html);
    $nav = '<div class="preview-strip"><strong>本地样式预览 · 示例数据</strong>';
    foreach ($pages as $slug => $label) {
        $nav .= '<a href="/'.$slug.'.html"'.($slug === $page ? ' aria-current="page"' : '').'>'.$label.'</a>';
    }
    $nav .= '</div>';
    $html = str_replace('</head>', $styles.'</head>', $html);
    $html = str_replace('data-workspace-content>', 'data-workspace-content>'.$nav, $html);
    if (str_starts_with($page, 'agent-')) {
        $html = str_replace('</body>', $nav.'</body>', $html);
    }
    $html = str_replace('</body>', $script.'</body>', $html);
    file_put_contents($output.'/'.$page.'.html', $html);
}
copy($output.'/center.html', $output.'/index.html');
echo $output.PHP_EOL;
