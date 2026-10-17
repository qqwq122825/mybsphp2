import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { JSDOM } from 'jsdom';
import { readSoftwareForm, formSubmission } from '../../resources/js/admin-ui/read-software.js';
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc';
const native = readFileSync(new URL('../../public/assets/js/peer-presence.js', import.meta.url), 'utf8');
const seed = { url: '/admin/applications/a/peer-presence', edit_url: '/admin/applications/a/edit', rows: [], enabled: true, total: 0, page: 1, last_page: 1, per_page: 25, pending_names: 0, refreshed_at: '2026-10-17 04:14:41' };
function fixture(enabled = true) {
    const doc = new JSDOM(`<main data-element-config><div data-config-form data-default-config-tab="entertainment"><form action="/admin/applications/a" method="post"><input name="_token" type="hidden" value="csrf"><section class="form-panel" data-config-panel="entertainment"><h2>娱乐设置</h2><label class="field"><span>启用同行显示</span><select name="peer_display_enabled"><option value="1" ${enabled ? 'selected' : ''}>开启</option><option value="0" ${enabled ? '' : 'selected'}>关闭</option></select></label><section data-peer-presence${enabled ? '' : ' hidden'}><script type="application/json" data-peer-presence-config>${JSON.stringify({ ...seed, enabled })}</script><a data-peer-refresh href="/admin/applications/a/edit">刷新名单</a><span data-peer-total></span><span data-peer-refreshed></span><p data-peer-error hidden></p><p data-peer-pending hidden></p><table><tbody data-peer-rows><tr><td>旧名单</td></tr></tbody></table><div data-peer-pager></div></section></section></form></div></main>`, { url: 'https://example.test/admin/applications/a/edit', runScripts: 'outside-only' });
    doc.window.eval(native); return doc;
}
const tick = () => new Promise(resolve => setTimeout(resolve, 0));
test('同行表格只在开启选项后显示，关闭时立即隐藏', () => {
    const dom = fixture(false), d = dom.window.document, select = d.querySelector('select'), table = d.querySelector('[data-peer-presence]');
    assert.equal(table.hidden, true);
    select.value = '1'; select.dispatchEvent(new dom.window.Event('change', { bubbles: true })); assert.equal(table.hidden, false);
    select.value = '0'; select.dispatchEvent(new dom.window.Event('change', { bubbles: true })); assert.equal(table.hidden, true);
    dom.window.close();
});
test('native-to-Vue adapter carries the table without changing ordinary form fields', () => {
    const dom = fixture(), page = readSoftwareForm(dom.window.document.querySelector('main'));
    assert.deepEqual(page.peerPresence, seed);assert.equal(page.activeTab, 'entertainment');
    assert.deepEqual(Object.fromEntries(formSubmission(page, { peer_display_enabled: '1' })), { _token: 'csrf', peer_display_enabled: '1' });
});
test('native refresh updates names and time as text, pages independently, and leaves unsaved settings intact', async () => {
    const dom = fixture(), { window } = dom, d = window.document;let calls = [];
    window.fetch = async (url, options) => { calls.push([String(url), options]);return { ok: true, json: async () => ({ ...seed, total: 30, last_page: 2, rows: [{ game_name: '<img src=x onerror=alert(1)> {{ payload }}', last_seen_at: '2026-10-17 04:15:00' }] }) }; };
    d.querySelector('select').value = '1';d.querySelector('[data-peer-refresh]').click();await tick();
    assert.match(d.querySelector('[data-peer-rows]').textContent, /<img src=x/);assert.equal(d.querySelector('[data-peer-rows] img'), null);
    assert.match(d.querySelector('[data-peer-rows]').textContent, /04:15:00/);assert.equal(d.querySelector('select').value, '1');
    assert.equal(calls[0][1].cache, 'no-store');assert.equal(calls[0][1].credentials, 'same-origin');
    d.querySelector('[data-peer-page="2"]').click();await tick();assert.match(calls[1][0], /peer_page=2/);
    dom.window.close();
});
test('native refresh failure preserves previous rows and permits retry', async () => {
    const dom = fixture(), d = dom.window.document;dom.window.fetch = async () => ({ ok: false });
    d.querySelector('[data-peer-refresh]').click();await tick();assert.equal(d.querySelector('[data-peer-rows]').textContent, '旧名单');assert.equal(d.querySelector('[data-peer-error]').hidden, false);assert.equal(d.querySelector('[data-peer-presence]').hasAttribute('aria-busy'), false);dom.window.close();
});
test('native handler does not duplicate Vue requests', async () => {
    const dom = fixture(), d = dom.window.document;d.querySelector('main').dataset.elementReady = 'true';let called = false;
    dom.window.fetch = async () => { called = true; };d.querySelector('[data-peer-refresh]').addEventListener('click', e => e.preventDefault());d.querySelector('[data-peer-refresh]').click();await tick();assert.equal(called, false);dom.window.close();
});
test('Element Plus table compiles and uses escaped property columns with an abortable read-only refresh', () => {
    const file = readFileSync(new URL('../../resources/js/admin-ui/PeerPresenceTable.vue', import.meta.url), 'utf8');
    const { descriptor, errors } = parse(file);assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: 'peer-table-test' });
    const result = compileTemplate({ id: 'peer-table-test', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } });assert.deepEqual(result.errors, []);
    assert.match(file, /prop="game_name"/);assert.match(file, /prop="last_seen_at"/);assert.match(file, /onBeforeUnmount/);assert.match(file, /AbortController/);assert.doesNotMatch(file, /v-html|submitForm/);
    const form = readFileSync(new URL('../../resources/js/admin-ui/SoftwareForm.vue', import.meta.url), 'utf8');
    assert.match(form, /String\(model\.peer_display_enabled\) === '1'/);
});

test('native refresh is consumed before workspace navigation can replace a dirty form', async () => {
    const dom = fixture(), d = dom.window.document;let workspaceCalls = 0;
    d.addEventListener('click', event => { if (event.target.closest('a')) workspaceCalls++; });
    dom.window.fetch = async () => ({ ok: true, json: async () => seed });
    d.querySelector('[data-peer-refresh]').click();await tick();assert.equal(workspaceCalls, 0);
    assert.equal(d.querySelector('select').value, '1');dom.window.close();
});
