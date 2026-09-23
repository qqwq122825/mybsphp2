import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { hiddenFields, readAction, readContent, readAdminList, submissionFields, decodePayload, csvCell, contentText } from '../../resources/js/admin-ui/read-list.js';

const dom = html => new JSDOM(html, { url: 'http://localhost/admin/risk-control/center?keyword=x' }).window.document;

test('software user status labels and severity survive the native-to-Vue adapter', () => {
    for (const [status, label, tone] of [
        ['active', '正常', 'success'], ['revoked', '已撤销', 'danger'],
        ['frozen', '已冻结', 'warning'], ['expired', '已到期', 'info'],
        ['disabled', '已停用', 'warning'], ['unknown', '未知状态', 'warning'],
    ]) {
        const cell = dom(`<div><span class="status-tag status-${status}">${label}</span></div>`).querySelector('div');
        assert.deepEqual(readContent(cell), [{ tag: label, tone }]);
    }
});

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

test('device-only risk metrics do not recreate an IP block counter', () => {
    const document = dom(`<div id="root" data-list-kind="risk"><section class="admin-table-shell"><header><h2>封控中心</h2><p class="risk-center-metrics">近 24 小时 <b>4</b> 次 · 设备 <b>2</b></p></header><table><thead><tr><th>设备</th><th>日志 IP</th></tr></thead><tbody><tr><td>DX3DRAQ2N741</td><td>2001:db8::10</td></tr></tbody></table></section></div>`);
    const page = readAdminList(document.querySelector('#root'));
    assert.deepEqual(page.metrics, [
        { label: '近 24 小时', value: 4, suffix: '次' },
        { label: '封控设备', value: 2, suffix: '台' },
    ]);
    assert.equal(page.columns[1].minWidth, 210);
});

test('quick block action submits only the device and its audit context', () => {
    const document = dom(`<form action="/admin/risk-control/global-blocks" method="post"><input type="hidden" name="_token" value="csrf"><input type="hidden" name="device_serial" value="DX3DRAQ2N741"><input type="hidden" name="source" value="quick"><input type="hidden" name="note" value="客户端请求日志快速拉黑"><button type="submit" onclick="return confirm('确定拉黑该请求的设备吗？')">快速拉黑</button></form>`);
    const action = readAction(document.querySelector('button'));
    assert.equal(action.confirm, '确定拉黑该请求的设备吗？');
    assert.deepEqual(submissionFields(action, []), [['_token', 'csrf'], ['device_serial', 'DX3DRAQ2N741'], ['source', 'quick'], ['note', '客户端请求日志快速拉黑']]);
});

test('long risk IP lists opt into single-line ellipsis while keeping the full export value', () => {
    const ips = ['2001:db8:2000:9872:f425:3ba4:a953:4363', '203.0.113.28', '2001:db8:846d:6400:5379:9235:1234:5678'].join('、');
    const document = dom(`<div id="root" data-list-kind="risk"><section class="admin-table-shell"><header><h2>封控中心</h2></header><table><thead><tr><th>封控对象</th><th>日志 IP</th><th>原因</th></tr></thead><tbody><tr><td><code>DX3DRAQ2N741</code></td><td class="risk-center-related" data-cell-ellipsis><code>${ips}</code></td><td>套卡探测达到阈值</td></tr><tr><td><code>NO-IP-DEVICE</code></td><td class="risk-center-related" data-cell-ellipsis><code>—</code></td><td>手动添加</td></tr></tbody></table></section></div>`);
    const page = readAdminList(document.querySelector('#root'));
    assert.equal(page.rows[0].cells[1].ellipsis, true);
    assert.equal(page.rows[0].cells[1].text, ips);
    assert.equal(contentText(page.rows[0].cells[1].content), ips);
    assert.equal(page.rows[0].cells[0].ellipsis, false);
    assert.equal(page.rows[0].cells[2].ellipsis, false);
    assert.equal(page.rows[1].cells[1].text, '—');
});
