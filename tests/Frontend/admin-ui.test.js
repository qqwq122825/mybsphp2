import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { hiddenFields, readAction, readContent, readAdminList, submissionFields, decodePayload, csvCell, contentText } from '../../resources/js/admin-ui/read-list.js';

const dom = html => new JSDOM(html, { url: 'http://localhost/admin/risk-control/center?keyword=x' }).window.document;

test('row submissions preserve the endpoint and CSRF token without including unrelated selections', () => {
    const document = dom(`<form id="release" method="post" action="/admin/release"><input type="hidden" name="_token" value="test-csrf"></form><input type="checkbox" form="release" name="block_keys[]" value="other" checked><button form="release" name="block_keys[]" value="target" onclick="return confirm('解除该设备？')">解除</button>`);
    const action = readAction(document.querySelector('button'));
    assert.equal(action.action, 'http://localhost/admin/release');
    assert.equal(action.confirm, '解除该设备？');
    assert.deepEqual(submissionFields(action, [{ selectionName: 'block_keys[]', id: 'other' }]), [['_token', 'test-csrf'], ['block_keys[]', 'target']]);
});

test('bulk actions submit selected keys and preserve action values', () => {
    const document = dom(`<form id="logs" method="post" action="/admin/logs"><input type="hidden" name="_token" value="csrf"><input type="hidden" name="view" value="detailed"></form><button form="logs" name="action" value="delete_selected" class="admin-table-action--danger">删除选定</button>`);
    const action = readAction(document.querySelector('button'), { bulk: true });
    assert.equal(action.tone, 'danger');
    assert.deepEqual(submissionFields(action, [{ selectionName: 'log_ids[]', id: '42' }]), [['_token', 'csrf'], ['view', 'detailed'], ['action', 'delete_selected'], ['log_ids[]', '42']]);
    assert.equal(hiddenFields(null).length, 0);
});

test('server content is text and whitelisted structure, never executable templates or attributes', () => {
    const document = dom(`<div id="cell"><strong>&lt;img src=x onerror=alert(1)&gt; {{ payload }}</strong><script>alert(1)</script><span onclick="alert(1)">text</span><span class="log-result is-danger">卡密不存在</span></div>`);
    const nodes = readContent(document.querySelector('#cell'));
    assert.equal(nodes[0].children[0].text, '<img src=x onerror=alert(1)> {{ payload }}');
    assert.equal(JSON.stringify(nodes).includes('onclick'), false);
    assert.deepEqual(nodes[2], { tag: '卡密不存在', tone: 'danger' });
});

test('a server list becomes Element Plus column, filter, action and pagination data', () => {
    const document = dom(`<div id="root"><section class="admin-table-shell"><header class="admin-table-shell-header"><h2>封控中心</h2><small>说明</small></header><form class="admin-filter-bar"><label class="admin-filter-bar__item"><strong>搜索字段</strong><select name="subject_type"><option value="all">全部</option><option value="device" selected>设备</option></select></label></form><table><thead><tr><th><input data-card-select-all type="checkbox"></th><th>设备</th><th class="admin-action-column">处置</th></tr></thead><tbody><tr><td><input data-card-select type="checkbox" name="keys[]" value="42"></td><td><code>LONG-DEVICE</code></td><td>已封禁</td></tr></tbody></table><div data-page="2" data-total="42" data-per-page="20"></div></section></div>`);
    const page = readAdminList(document.querySelector('#root'));
    assert.equal(page.columns[0].selection, true);
    assert.equal(page.columns[2].action, true);
    assert.equal(page.columns[2].width, 116);
    assert.equal(page.filter.fields[0].value, 'device');
    assert.equal(page.rows[0].id, '42');
    assert.deepEqual(page.pagination, { page: 2, total: 42, perPage: 20 });
});

test('payload decoding supports Unicode and rejects malformed data', () => {
    assert.equal(decodePayload(Buffer.from(JSON.stringify({ 提示: '已封禁' })).toString('base64')), '{\n  "提示": "已封禁"\n}');
    assert.equal(decodePayload('invalid'), '{}');
});

test('CSV export escapes quotes and neutralizes spreadsheet formulas', () => {
    assert.equal(csvCell('one,"two"'), '"one,""two"""');
    assert.equal(csvCell('=SUM(A1:A9)'), '"\'=SUM(A1:A9)"');
    assert.equal(csvCell('设备'), '"设备"');
    assert.equal(contentText(readContent(dom('<td></td><div id="time"><span>2026-09-10</span><small>17:08:35</small></div>').querySelector('#time'))), '2026-09-10 17:08:35');
});
