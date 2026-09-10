import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { JSDOM } from 'jsdom';
import { compile, createSSRApp, h } from 'vue';
import { parse } from 'vue/compiler-sfc';
import { renderToString } from 'vue/server-renderer';

// Render the actual navigation branches, with lightweight component stubs.
// Keep visibility rules in AgentApp.vue as the single source of truth.
const { descriptor } = parse(readFileSync(new URL('../../resources/js/agent-ui/AgentApp.vue', import.meta.url), 'utf8'));
function findElement(node, tag) {
    if (node.tag === tag) return node;
    for (const child of node.children || []) {
        const found = findElement(child, tag);
        if (found) return found;
    }
}
const navigationTemplate = ['el-aside', 'el-tabs'].map(tag => {
    const node = findElement(descriptor.template.ast, tag);
    assert.ok(node, `${tag} navigation component must remain available`);
    return node.loc.source;
}).join('');
const render = compile(`<div>${navigationTemplate}</div>`);
const labels = ['中控台', '卡管理', '余额制卡', '财务管理'];
const wrapper = tag => ({ setup: (_, { slots }) => () => h(tag, slots.default?.()) });

async function navigation({ mobile = false, login = false, collapsed = false } = {}) {
    const app = createSSRApp({
        render,
        setup: () => ({
            mobile, collapsed,
            page: { login, navigation: labels.map((label, index) => ({ label, href: `/agent/${index}` })) },
            activeNav: '/agent/0', navigate: () => {}, icons: labels.map(() => wrapper('span')),
        }),
        components: {
            ElAside: wrapper('aside'), ElMenu: wrapper('nav'), ElMenuItem: wrapper('a'), ElIcon: wrapper('span'),
            ElTabs: wrapper('section'),
            ElTabPane: { props: ['label'], setup: props => () => h('button', props.label) },
        },
    });
    return new JSDOM(await renderToString(app)).window.document;
}

test('desktop agent pages have sidebar navigation without duplicate top tabs', async () => {
    const document = await navigation();
    assert.deepEqual([...document.querySelectorAll('aside a')].map(node => node.textContent), labels);
    assert.equal(document.querySelectorAll('section').length, 0);
});

test('mobile agent pages keep all four top destinations without a sidebar', async () => {
    const document = await navigation({ mobile: true });
    assert.equal(document.querySelectorAll('aside').length, 0);
    assert.deepEqual([...document.querySelectorAll('section button')].map(node => node.textContent), labels);
});

test('agent login pages do not expose either navigation surface', async () => {
    for (const mobile of [false, true]) {
        const document = await navigation({ mobile, login: true });
        assert.equal(document.querySelectorAll('aside, section').length, 0);
    }
});

test('collapsing the desktop sidebar does not recreate the duplicate tabs', async () => {
    const document = await navigation({ collapsed: true });
    assert.equal(document.querySelectorAll('aside, section').length, 0);
});
