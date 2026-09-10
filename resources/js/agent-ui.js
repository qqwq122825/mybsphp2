import { createApp } from 'vue';
import 'element-plus/dist/index.css';
import '../css/admin-ui.css';
import AgentApp from './agent-ui/AgentApp.vue';
import { readAgent } from './agent-ui/read-agent';

function initialize() {
    const root = document.querySelector('[data-element-agent]');
    if (!root || root.dataset.elementReady) return;
    const page = readAgent(root);
    const host = document.createElement('div');
    host.dataset.agentHost = '';
    root.after(host);
    createApp(AgentApp, { page, preview: document.documentElement.hasAttribute('data-admin-preview') }).mount(host);
    root.hidden = true;
    root.style.display = 'none';
    root.dataset.elementReady = 'true';
    // Remove only the old login centering/background layout; ElContainer now owns it.
    document.body.classList.remove('agent-login-page');
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
