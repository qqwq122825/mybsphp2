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

function titleWorkspace(t, { bootstrap = false } = {}) {
    const dom = new JSDOM(`<div data-workspace-tabs data-workspace-base-url="http://localhost/admin/applications" ${bootstrap ? 'data-workspace-bootstrap-url="http://localhost/admin/applications/a/edit" data-workspace-bootstrap-title="同名软件 · 软件配置"' : ''}><a data-workspace-base-tab class="is-active" href="/admin/applications">软件管理</a></div><main data-workspace-content>${bootstrap ? '配置' : '<a href="/admin/applications/a/edit" data-workspace-open data-workspace-title="1001-软件配置">配置 A</a><a href="/admin/applications/b/edit" data-workspace-open data-workspace-title="1002-软件配置">配置 B</a>'}</main>`, { url: 'http://localhost/admin/applications', runScripts: 'outside-only' });
    t.after(() => dom.window.close());
    const { window } = dom;
    window.scrollTo = () => {};
    window.HTMLElement.prototype.scrollIntoView = () => {};
    window.alert = message => assert.fail(message);
    const titles = new Map(['a', 'b'].map(id => [id, '同名软件 · 软件配置']));
    const escape = text => text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('"', '&quot;');
    window.fetch = async url => {
        const id = url.includes('/b/') ? 'b' : 'a';
        const base = url === 'http://localhost/admin/applications';
        return { ok: true, url, text: async () => `<html><body><div data-workspace-tabs ${base ? '' : `data-workspace-page-title="${escape(titles.get(id))}"`}><a data-workspace-base-tab class="is-active" href="/admin/applications">软件管理</a></div><main data-workspace-content>${base ? '列表' : `<p>配置 ${id}</p><form action="/admin/applications/${id}/edit" method="post"><input name="name"></form>`}</main></body></html>` };
    };
    window.eval(readFileSync(new URL('../../public/assets/js/mybsphp.js', import.meta.url), 'utf8'));
    return { window, document: window.document, titles };
}

test('workspace uses names from the response while same-name software keeps separate URL-keyed tabs', async t => {
    const { document } = titleWorkspace(t);
    document.querySelector('a[data-workspace-open]').click();
    await tick();
    document.querySelector('[data-workspace-base-tab]').click();
    document.querySelectorAll('a[data-workspace-open]')[1].click();
    await tick();
    const tabs = [...document.querySelectorAll('[data-workspace-tab]')];
    assert.equal(tabs.length, 2);
    assert.deepEqual(tabs.map(tab => tab.querySelector('span').textContent), ['同名软件 · 软件配置', '同名软件 · 软件配置']);
    assert.notEqual(tabs[0].dataset.workspaceTab, tabs[1].dataset.workspaceTab);
    tabs[0].click();
    assert.equal(document.querySelector('main p').textContent, '配置 a');
    tabs[0].querySelector('[data-workspace-tab-close]').click();
    assert.equal(document.querySelectorAll('[data-workspace-tab]').length, 1);
    assert.equal(document.querySelector('main p').textContent, '配置 b');
});

test('renames refresh tab text and its full tooltip after component save, cached-list reopen and native save', async t => {
    const { document, window, titles } = titleWorkspace(t);
    document.querySelector('a[data-workspace-open]').click();
    await tick();
    const tab = document.querySelector('[data-workspace-tab]');
    const key = tab.dataset.workspaceTab;
    const renamed = '很长的软件名称 <img src=x onerror=alert(1)> "A&B" · 软件配置';
    titles.set('a', renamed);
    let result;
    document.dispatchEvent(new window.CustomEvent('workspace:submit', { cancelable: true, detail: {
        action: key, method: 'post', fields: [['_method', 'PUT']], complete: response => { result = response; },
    } }));
    await tick();
    assert.equal(result.ok, true);
    assert.equal(tab.querySelector('span').textContent, renamed);
    assert.equal(tab.title, renamed);
    assert.equal(tab.querySelector('img'), null, 'titles must be text, never parsed HTML');
    document.querySelector('[data-workspace-base-tab]').click();
    document.querySelector('a[data-workspace-open]').click();
    await tick();
    assert.equal(document.querySelectorAll('[data-workspace-tab]').length, 1);
    assert.equal(tab.title, renamed, 'cached list label must not replace the current server name');
    titles.set('a', '再次改名 · 软件配置');
    document.querySelector('form').dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
    await tick();
    assert.equal(tab.title, '再次改名 · 软件配置');
    assert.equal(tab.dataset.workspaceTab, key);
});

test('direct configuration URL bootstraps a named tab without changing its URL identity', async t => {
    const { document, window } = titleWorkspace(t, { bootstrap: true });
    await tick();
    const tab = document.querySelector('[data-workspace-tab]');
    assert.equal(tab.title, '同名软件 · 软件配置');
    assert.equal(tab.querySelector('span').textContent, tab.title);
    assert.equal(tab.dataset.workspaceTab, 'http://localhost/admin/applications/a/edit');
    assert.equal(window.location.href, 'http://localhost/admin/applications');
});

test('workspace toggle keeps proxy JSON and displays the generated endpoint after the standalone POST', async t => {
    const { readSoftwareForm } = await import('../../resources/js/admin-ui/read-software.js');
    const address = 'https://license.example/lk_' + 'a'.repeat(32);
    const page = enabled => {
        const proxy = { action: 'http://localhost/admin/applications/a/proxy-link', method: 'post', fields: [['_token', 'csrf']], enabled: enabled ? '1' : '0', permission: 'replay', address: enabled ? address : null };
        return `<html><body><div data-workspace-tabs data-workspace-page-title="软件配置"></div><main data-workspace-content><div data-element-config><div data-config-form data-default-config-tab="proxy"><button data-config-tab="proxy"><strong>代理联动</strong></button><form action="/admin/applications/a" method="post"><input type="hidden" name="_method" value="PUT"></form><div data-config-panel="proxy"><script type="application/json" data-proxy-link-config>${JSON.stringify(proxy)}</script></div></div></div></main></body></html>`;
    };
    const dom = new JSDOM('<div data-workspace-tabs><a class="is-active">软件管理</a></div><main data-workspace-content><a href="/admin/applications/a/edit" data-workspace-open>配置</a></main>', { url: 'http://localhost/admin/applications', runScripts: 'outside-only' });
    t.after(() => dom.window.close());
    const { window } = dom;
    window.scrollTo = () => {};
    window.HTMLElement.prototype.scrollIntoView = () => {};
    const requests = [];
    window.fetch = async (url, options) => {
        requests.push({ url, options });
        return { ok: true, url, text: async () => page(options.method === 'POST') };
    };
    window.eval(readFileSync(new URL('../../public/assets/js/mybsphp.js', import.meta.url), 'utf8'));
    window.document.querySelector('[data-workspace-open]').click();
    await tick();
    let config = readSoftwareForm(window.document.querySelector('[data-element-config]'));
    assert.equal(config.proxy.enabled, '0');
    assert.equal(config.proxy.address, null);
    let result;
    window.document.dispatchEvent(new window.CustomEvent('workspace:submit', { cancelable: true, detail: {
        action: config.proxy.action, method: config.proxy.method,
        fields: [...config.proxy.fields, ['enabled', '1'], ['permission', 'replay'], ['action', 'save']], complete: response => { result = response; },
    } }));
    await tick();
    assert.equal(result.ok, true);
    const submitted = requests[1];
    assert.equal(submitted.url, 'http://localhost/admin/applications/a/proxy-link');
    assert.equal(submitted.options.body.get('enabled'), '1');
    assert.equal(submitted.options.body.get('action'), 'save');
    assert.equal(submitted.options.body.has('_method'), false);
    config = readSoftwareForm(window.document.querySelector('[data-element-config]'));
    assert.equal(config.proxy.enabled, '1');
    assert.equal(config.proxy.address, address);
    assert.equal(config.activeTab, 'proxy');
});
