import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../../public/assets/css/mybsphp.css', import.meta.url), 'utf8');

test('collapsed sidebar removes the auto-margin arrow from flex layout', () => {
    assert.match(css, /\.dashboard-page\.is-sidebar-collapsed \.admin-sidebar \.nav-group summary i,\s*\.dashboard-page\.is-sidebar-collapsed \.admin-sidebar \.nav-children\s*\{\s*display: none;/);
});

test('mobile drawer restores text and arrows after desktop collapse', () => {
    assert.match(css, /\.dashboard-page\.is-sidebar-collapsed \.admin-sidebar \.nav-group summary i\s*\{\s*display: inline;\s*max-width: none;\s*visibility: visible;\s*opacity: 1;\s*transform: none;/);
});
