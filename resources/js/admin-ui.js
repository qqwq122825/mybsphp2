import { createApp } from 'vue';
import 'element-plus/dist/index.css';
import '../css/admin-ui.css';
import AdminList from './admin-ui/AdminList.vue';
import { readAdminList } from './admin-ui/read-list';
import SoftwareList from './admin-ui/SoftwareList.vue';
import SoftwareForm from './admin-ui/SoftwareForm.vue';
import { readSoftwareList, readSoftwareForm } from './admin-ui/read-software';
import { readUserDialogs } from './admin-ui/read-users';

const mounted = new Map();
const selector = '[data-element-list], [data-element-software], [data-element-config]';
function initialize() {
    for (const [root, app] of mounted) {
        if (!root.isConnected) { app.unmount(); mounted.delete(root); }
    }
    document.querySelectorAll(selector).forEach(root => {
        if (mounted.has(root)) return;
        const isSoftware = root.hasAttribute('data-element-software');
        const isConfig = root.hasAttribute('data-element-config');
        const page = isSoftware ? readSoftwareList(root) : isConfig ? readSoftwareForm(root) : readAdminList(root);
        if (root.dataset.listKind === 'users') page.userDialogs = readUserDialogs(root);
        // Use a separate host: preserve the server-rendered view as a no-JS
        // fallback and as the source when the existing workspace restores tabs.
        const host = document.createElement('div');
        host.dataset.elementHost = '';
        if (root.nextElementSibling?.matches('[data-element-host]')) root.nextElementSibling.remove();
        root.after(host);
        const app = createApp(isSoftware ? SoftwareList : isConfig ? SoftwareForm : AdminList, { page, preview: document.documentElement.hasAttribute('data-admin-preview') });
        app.mount(host);
        root.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
        root.hidden = true;
        root.dataset.elementReady = 'true';
        mounted.set(root, { unmount() { app.unmount(); host.remove(); } });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
// Existing Blade workspace navigation may insert a new server-rendered page.
new MutationObserver(mutations => {
    if (mutations.some(mutation => [...mutation.addedNodes, ...mutation.removedNodes].some(node => node.nodeType === 1 && (node.matches?.(selector) || node.querySelector?.(selector))))) initialize();
}).observe(document.body, { childList: true, subtree: true });
