import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { JSDOM } from 'jsdom';

const tick = () => new Promise(resolve => setTimeout(resolve, 0));

test('workspace ignores component-handled native submits and preserves its transport for validated submissions', async () => {
    const dom = new JSDOM(`<div data-workspace-tabs data-workspace-base-url="http://localhost/admin/applications"><a class="is-active" href="/admin/applications">软件管理</a></div><main data-workspace-content><a href="/admin/applications/example/edit" data-workspace-open data-workspace-title="软件配置">配置</a></main>`, { url: 'http://localhost/admin/applications', runScripts: 'outside-only' });
    const { window } = dom;
    const requests = [];
    window.scrollTo = () => {};
    window.HTMLElement.prototype.scrollIntoView = () => {};
    window.fetch = async (url, options) => {
        requests.push({ url, options });
        return { ok: true, url, text: async () => '<html><head><title>软件配置</title></head><body><main data-workspace-content><div data-element-host><form><input value="edited"></form></div></main></body></html>' };
    };
    window.eval(readFileSync(new URL('../../public/assets/js/mybsphp.js', import.meta.url), 'utf8'));
    window.document.querySelector('[data-workspace-open]').click();
    await tick();
    assert.equal(requests.length, 1);
    const form = window.document.querySelector('form');
    form.addEventListener('submit', event => event.preventDefault());
    form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
    await tick();
    assert.equal(requests.length, 1, 'the old workspace must not issue an empty GET after Vue validation');
    let result;
    const accepted = window.document.dispatchEvent(new window.CustomEvent('workspace:submit', { cancelable: true, detail: {
        action: 'http://localhost/admin/applications/example', method: 'post',
        fields: [['_token', 'csrf'], ['_method', 'PUT'], ['name', 'edited']], complete: response => { result = response; },
    } }));
    assert.equal(accepted, false);
    await tick();
    assert.equal(result.ok, true);
    assert.equal(requests[1].options.method, 'POST');
    assert.equal(requests[1].options.headers['X-Workspace-Url'], 'http://localhost/admin/applications/example/edit');
    assert.equal(requests[1].options.body.get('_token'), 'csrf');
    assert.equal(requests[1].options.body.get('_method'), 'PUT');
    assert.equal(requests[1].options.body.get('name'), 'edited');
    dom.window.close();
});
