import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { deleteSubmission, formSubmission, formValues, readSoftwareForm, readSoftwareList } from '../../resources/js/admin-ui/read-software.js';
import { readAdminList } from '../../resources/js/admin-ui/read-list.js';
import { userBulkFields, userDetailModel } from '../../resources/js/admin-ui/read-users.js';

const dom = html => new JSDOM(html, { url: 'http://localhost/admin/applications' }).window.document;

test('proxy linkage keeps its own endpoint and does not leak into the normal software form submission', () => {
    const proxy = { action: '/admin/applications/app/proxy-link', method: 'post', fields: [['_token', 'csrf']], enabled: '1', permission: 'replay', token: 'node-secret', address: 'https://license.example/lk_' + 'a'.repeat(32), credentials: [] };
    const root = dom(`<main data-element-config><div data-config-form data-default-config-tab="proxy"><button data-config-tab="proxy"><strong>代理联动</strong></button><form method="post" action="/admin/applications/app"><input type="hidden" name="_token" value="csrf"><input type="hidden" name="_method" value="PUT"></form><script type="application/json" data-proxy-link-config>${JSON.stringify(proxy)}</script><form action="${proxy.action}" method="post"><input name="enabled" value="1"></form></div></main>`).querySelector('main');
    const page = readSoftwareForm(root);
    assert.deepEqual(page.proxy, proxy);
    assert.equal(page.activeTab, 'proxy');
    const normal = Object.fromEntries(formSubmission(page, {}));
    assert.deepEqual(normal, { _token: 'csrf', _method: 'PUT' });
    assert.notEqual(page.action, page.proxy.action);
});

test('software creation keeps the server-suggested code editable and submits manual overrides verbatim', () => {
    const root = dom('<main data-element-config><form action="/admin/applications" method="post"><section class="form-panel"><label class="field"><span>软件代号</span><input name="code" value="12345678" inputmode="numeric" pattern="[0-9]+" maxlength="20" required></label></section></form></main>').querySelector('main');
    const form = readSoftwareForm(root);
    const model = formValues(form.groups.flatMap(group => group.fields));
    const control = form.groups[0].fields[0].controls[0];
    assert.equal(form.editing, false);
    assert.equal(model.code, '12345678');
    assert.equal(control.readonly, false);
    assert.equal(control.inputmode, 'numeric');
    assert.equal(Object.fromEntries(formSubmission(form, model)).code, '12345678');
    model.code = '00123456';
    assert.equal(Object.fromEntries(formSubmission(form, model)).code, '00123456');
});

test('software forms preserve hidden settings, CSRF, method, named values and readonly identity', () => {
    const root = dom(`<main data-element-config data-form-errors='{"name":["名称已存在"]}'><div data-config-form data-default-config-tab="limits"><h1>软件配置</h1><button data-config-tab="basic"><strong>基本配置</strong></button><button data-config-tab="limits"><strong>限开控制</strong></button><form action="/admin/applications/example" method="post"><input type="hidden" name="_token" value="csrf"><input type="hidden" name="_method" value="PUT"><input type="hidden" name="encryption_mode" value="hybrid_rsa_aes_hmac"><div data-config-panel="basic"><section class="form-panel"><h2>基本配置</h2><label class="software-basic-row"><strong>代号</strong><input value="1004" readonly></label><label class="software-basic-row"><strong>软件名称</strong><input name="name" value="客户端" maxlength="120" required></label><label class="software-basic-row"><strong>说明</strong><textarea name="description">&lt;script&gt; {{ text }}</textarea></label></section></div><div data-config-panel="limits"><section class="form-panel"><h2>限开控制</h2><label class="field"><span>扣除时长</span><input type="number" name="rebind_deduction_value" value="0" min="0" max="3650" required><select name="rebind_deduction_unit"><option value="hour">小时</option><option value="day" selected>天</option></select></label></section></div></form></div></main>`).querySelector('main');
    const form = readSoftwareForm(root);
    assert.equal(form.editing, true);
    assert.equal(form.activeTab, 'limits');
    assert.deepEqual(form.errors.name, ['名称已存在']);
    const model = formValues(form.groups.flatMap(group => group.fields));
    assert.equal(model.rebind_deduction_value, 0);
    assert.equal(model.rebind_deduction_unit, 'day');
    assert.equal(model.description, '<script> {{ text }}');
    const payload = Object.fromEntries(formSubmission(form, { ...model, name: '新名称' }));
    assert.equal(payload._token, 'csrf');
    assert.equal(payload._method, 'PUT');
    assert.equal(payload.encryption_mode, 'hybrid_rsa_aes_hmac');
    assert.equal(payload.name, '新名称');
    assert.equal(Object.keys(payload).some(key => key.startsWith('readonly_')), false);
});

test('software cards only expose links and deletion controls rendered by Blade permissions', () => {
    const root = dom(`<main><h1>软件管理</h1><article class="software-card"><div class="software-card-title"><h2>&lt;img src=x&gt;</h2><p><strong>1004</strong></p></div><span class="status-tag status-active">正常开放</span><div class="software-mode-line"><strong>卡串验证模式</strong></div><footer class="software-card-actions"><a href="/admin/applications/example/users" data-workspace-open data-workspace-title="&lt;img src=x&gt; · 用户管理">▣ 用户管理</a></footer></article></main>`).querySelector('main');
    const card = readSoftwareList(root).applications[0];
    assert.equal(card.name, '<img src=x>');
    assert.equal(card.deletion, null);
    assert.equal(card.links.length, 1);
    assert.equal(card.links[0].workspace, true);
    assert.equal(card.links[0].label, '用户管理');
    assert.equal(card.links[0].title, '<img src=x> · 用户管理');
    assert.equal(card.links[0].href, 'http://localhost/admin/applications/example/users');
});

test('deletion includes only explicitly chosen allowed flags, and all dependents for the master', () => {
    const deletion = { fields: [['_token', 'csrf'], ['_method', 'DELETE']], options: ['delete_application', 'delete_users', 'delete_cards', 'delete_card_types'].map(name => ({ name })) };
    assert.deepEqual(deleteSubmission(deletion, ['delete_cards', 'unknown']), [['_token', 'csrf'], ['_method', 'DELETE'], ['delete_cards', '1']]);
    assert.equal(deleteSubmission(deletion, ['delete_application']).length, 6);
    assert.equal(deleteSubmission(deletion, []).length, 2);
});

test('software user details preserve target route, raw dates, zero/default and negative overrides', () => {
    const root = dom(`<main data-list-kind="users"><section class="bsphp-user-panel"><h2>软件用户</h2><table><thead><tr><th><input data-card-select-all></th><th>用户名</th><th class="bsphp-user-action-column">操作</th></tr></thead><tbody><tr><td><input data-card-select name="user_ids[]" value="12"></td><td>demo</td><td><button data-user-detail data-action="/admin/apps/a/users/u" data-title="账号 demo" data-expires="2026-09-16T22:17" data-account-limit="0" data-machine-limit="-1" data-note="&lt;script&gt;">详情</button></td></tr></tbody></table><span data-total="62" data-page="2" data-per-page="30"></span></section></main>`).querySelector('main');
    const page = readAdminList(root);
    const detail = page.rows[0].cells[2].content[0].action.userDetail;
    assert.equal(detail.action, '/admin/apps/a/users/u');
    assert.equal(page.columns[2].action, true);
    assert.equal(page.pagination.total, 62);
    assert.deepEqual(userDetailModel(detail), { expires_at: '2026-09-16T22:17', bound_device: '', account_concurrency_limit: 0, machine_concurrency_limit: -1, note: '<script>' });
});

test('bulk maintenance sends the chosen row IDs once and preserves the application scope', () => {
    const bulk = { fields: [['_token', 'csrf'], ['application_id', '1004'], ['selection_mode', 'ids'], ['scope', 'all'], ['confirmed', '1'], ['id_list', '']] };
    const fields = userBulkFields(bulk, [{ id: '12' }, { id: '19' }], 'add_time', 60);
    assert.equal(fields.filter(([key]) => key === 'id_list').length, 1);
    assert.equal(Object.fromEntries(fields).id_list, '12,19');
    assert.equal(Object.fromEntries(fields).application_id, '1004');
    assert.equal(Object.fromEntries(fields).action_value, 60);
});

test('software users empty tables do not become selectable phantom records', () => {
    const root = dom('<main><section class="bsphp-user-panel"><h2>用户管理</h2><table><thead><tr><th>用户名</th></tr></thead><tbody><tr><td class="bsphp-table-empty">无数据</td></tr></tbody></table></section></main>').querySelector('main');
    assert.deepEqual(readAdminList(root).rows, []);
});
