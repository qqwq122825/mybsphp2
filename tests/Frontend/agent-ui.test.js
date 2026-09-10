import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { readAgent, purchaseTotal } from '../../resources/js/agent-ui/read-agent.js';
import { formEndpoint, readAction, submissionFields } from '../../resources/js/admin-ui/read-list.js';

const doc = html => new JSDOM(html, { url: 'http://localhost/agent/cards' }).window.document;

test('agent action endpoints cannot be clobbered by named hidden action fields', () => {
    const document = doc('<form method="post" action="/agent/cards/42/action"><input type="hidden" name="_token" value="csrf"><input type="hidden" name="action" value="delete_card"><input type="hidden" name="confirmed" value="1"><button type="submit">确认删除</button></form>');
    const form = document.querySelector('form');
    Object.defineProperty(form, 'action', { value: form.querySelector('[name="action"]') });
    assert.equal(formEndpoint(form), 'http://localhost/agent/cards/42/action');
    const action = readAction(form.querySelector('button'));
    assert.equal(action.action, 'http://localhost/agent/cards/42/action');
    assert.deepEqual(submissionFields(action), [['_token', 'csrf'], ['action', 'delete_card'], ['confirmed', '1']]);
});

test('agent purchase schema uses server prices and original constraints, including free cards', () => {
    const root = doc(`<main data-element-agent data-balance="20000"><div class="agent-main"><h1>余额购卡</h1><form data-agent-purchase-form method="post" action="/agent/purchase"><input type="hidden" name="_token" value="csrf"><label><strong>卡串类型</strong><select name="card_type_id" required><option value="">请选择</option><option value="12" data-price="1501">体验卡</option><option value="13" data-price="0">免费卡</option></select></label><label><strong>数量</strong><input type="number" name="quantity" min="1" max="500" value="1" required></label><button type="submit">确认制卡</button></form></div></main>`).querySelector('main');
    const page = readAgent(root);
    assert.equal(page.kind, 'purchase');
    assert.equal(page.balance, 20000);
    assert.deepEqual(page.form.prices, { 12: 1501, 13: 0 });
    assert.equal(page.form.controls[1].controls[0].max, 500);
    assert.equal(page.form.action, 'http://localhost/agent/purchase');
    assert.equal(purchaseTotal(1501, 3), 4503);
    assert.equal(purchaseTotal(0, 500), 0);
    assert.equal(purchaseTotal(1, 500), 500);
    for (const quantity of [0, -1, 501, 1.1, Infinity, NaN]) assert.equal(purchaseTotal(1501, quantity), 0);
    assert.equal(purchaseTotal(Number.MAX_SAFE_INTEGER, 2), 0);
});

test('login schema keeps password semantics, captcha toggles and server errors', () => {
    const root = doc(`<main data-element-agent data-agent-login data-form-errors='{"username":["登录失败"]}'><h1>代理登录</h1><form class="agent-login-form" action="/agent/login" method="post"><input type="hidden" name="_token" value="csrf"><label><span>用户名</span><input name="username" autocomplete="username" required></label><label><span>密码</span><input type="password" name="password" autocomplete="current-password" required></label><button type="submit">登入</button></form></main>`).querySelector('main');
    const page = readAgent(root);
    assert.equal(page.login, true);
    assert.equal(page.form.controls[1].controls[0].type, 'password');
    assert.equal(page.form.controls[1].controls[0].autocomplete, 'current-password');
    assert.equal(page.form.controls.some(field => field.controls[0].name === 'captcha'), false);
    assert.deepEqual(page.errors.username, ['登录失败']);
});

test('agent table only exposes server-rendered actions and retains pagination and escaped content', () => {
    const root = doc(`<main data-element-agent><div class="agent-main"><h1>卡列表</h1><table><thead><tr><th>卡号</th><th>操作</th></tr></thead><tbody><tr><td><code>&lt;img src=x&gt;</code></td><td class="agent-row-actions"><form action="/agent/cards/1/action" method="post"><input type="hidden" name="action" value="unbind"><button type="submit">解绑</button></form></td></tr></tbody></table><span data-agent-pagination data-total="45" data-page="2" data-per-page="20"></span></div></main>`).querySelector('main');
    const page = readAgent(root);
    assert.equal(page.table.rows.length, 1);
    assert.equal(page.table.rows[0].actions.length, 1);
    assert.equal(page.table.rows[0].actions[0].label, '解绑');
    assert.equal(page.table.rows[0].cells[0].text, '<img src=x>');
    assert.equal(page.table.page, 2);
    assert.equal(page.table.total, 45);
});

test('server unbind confirmation preserves both deduction choices without inventing a choice', () => {
    const root = doc(`<main data-element-agent><div data-agent-unbind-modal><h2>确认解绑</h2><p>扣除时间？</p><li>DEMO</li><form action="/agent/cards/7/action" method="post"><input type="hidden" name="_token" value="csrf"><input type="hidden" name="action" value="unbind"><button type="submit" name="deduct_time" value="1">扣除时间</button><button type="submit" name="deduct_time" value="0">不扣时间</button><button type="button">取消</button></form></div></main>`).querySelector('main');
    const modal = readAgent(root).modal;
    assert.equal(modal.actions.length, 2);
    assert.deepEqual(submissionFields(modal.actions[0]).at(-1), ['deduct_time', '1']);
    assert.deepEqual(submissionFields(modal.actions[1]).at(-1), ['deduct_time', '0']);
});
