import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../../public/assets/css/activation-query.css', import.meta.url), 'utf8');
const view = readFileSync(new URL('../../resources/views/public/activation-query.blade.php', import.meta.url), 'utf8');

test('public activation query declares a safe mobile viewport and cache-busted stylesheet', () => {
    assert.match(view, /viewport-fit=cover/);
    assert.match(view, /activation-query\.css\?v=20261017-responsive/);
});

test('public activation query also compacts for mobile browsers with wide CSS viewports', () => {
    assert.match(css, /@media \(max-width:\s*1024px\)/);
    assert.match(css, /html,[\s\S]*?body[\s\S]*?overflow-x:\s*clip/);
    assert.match(css, /@media \(max-width:\s*1024px\)[\s\S]*?align-items:\s*start/);
    assert.match(css, /@media \(max-width:\s*1024px\)[\s\S]*?\.activation-query-actions[\s\S]*?flex-direction:\s*column/);
});

test('public activation query fits narrow phones without horizontal navigation overflow', () => {
    assert.match(css, /min-height:\s*100svh/);
    assert.match(css, /max\(12px, env\(safe-area-inset-top\)\)/);
    assert.match(css, /grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\)/);
    assert.match(css, /\.public-tools-nav a[\s\S]*?white-space:\s*nowrap/);
    assert.match(css, /@media \(max-width:\s*360px\)/);
});

test('public activation query stacks controls and result details on mobile', () => {
    assert.match(css, /\.activation-query-actions[\s\S]*?flex-direction:\s*column/);
    assert.match(css, /\.activation-query-result-details[\s\S]*?grid-template-columns:\s*1fr/);
    assert.match(css, /\.activation-query-body input[\s\S]*?font-size:\s*16px/);
    assert.match(css, /overflow-wrap:\s*anywhere/);
});
