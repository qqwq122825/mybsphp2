(() => {
    const states = new WeakMap();
    const emptyText = data => data.enabled ? '暂无在线同行，进入对局上报后显示。' : '同行显示已关闭，开启并保存后接收上报。';
    function syncVisibility(select) {
        const root = select.closest('form')?.querySelector('[data-peer-presence]');
        if (root) root.hidden = select.value !== '1';
    }
    function render(root, data) {
        const body = root.querySelector('[data-peer-rows]');
        body.replaceChildren();
        for (const peer of data.rows) {
            const row = document.createElement('tr');
            for (const value of [peer.game_name, peer.last_seen_at]) {
                const cell = document.createElement('td'); cell.textContent = value; row.append(cell);
            }
            body.append(row);
        }
        if (!data.rows.length) {
            const row = document.createElement('tr'), cell = document.createElement('td');
            cell.colSpan = 2; cell.className = 'peer-presence-empty'; cell.textContent = emptyText(data); row.append(cell); body.append(row);
        }
        root.querySelector('[data-peer-total]').textContent = `共 ${data.total} 个昵称`;
        root.querySelector('[data-peer-refreshed]').textContent = `名单刷新于 ${data.refreshed_at}（北京时间）`;
        const pending = root.querySelector('[data-peer-pending]');
        pending.hidden = !data.pending_names; pending.textContent = `有 ${data.pending_names} 条旧记录等待下次心跳补全昵称。`;
        const pager = root.querySelector('[data-peer-pager]'); pager.replaceChildren();
        function link(label, page) {
            const item = document.createElement('a'), url = new URL(data.edit_url, location.href);
            url.searchParams.set('config_tab', 'entertainment'); url.searchParams.set('peer_page', page);
            item.href = url.href; item.dataset.peerPage = page; item.textContent = label; pager.append(item);
        }
        if (data.page > 1) link('上一页', data.page - 1);
        const page = document.createElement('span'); page.textContent = `第 ${data.page} / ${data.last_page} 页`; pager.append(page);
        if (data.page < data.last_page) link('下一页', data.page + 1);
    }
    document.querySelectorAll('select[name="peer_display_enabled"]').forEach(syncVisibility);
    document.addEventListener('change', event => {
        if (event.target instanceof HTMLSelectElement && event.target.name === 'peer_display_enabled') syncVisibility(event.target);
    });
    document.addEventListener('click', async event => {
        if (!(event.target instanceof Element)) return;
        const trigger = event.target.closest('[data-peer-refresh], [data-peer-page]');
        const root = trigger?.closest('[data-peer-presence]');
        if (!root || root.closest('[data-element-ready="true"]')) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
        // Consume these links before the workspace's delegated navigation handler.
        event.preventDefault(); event.stopPropagation();
        let state = states.get(root);
        if (!state) { state = { data: JSON.parse(root.querySelector('[data-peer-presence-config]').textContent), busy: false }; states.set(root, state); }
        if (state.busy) return;
        state.busy = true; root.setAttribute('aria-busy', 'true');
        root.querySelectorAll('[data-peer-refresh], [data-peer-page]').forEach(item => item.setAttribute('aria-disabled', 'true'));
        const error = root.querySelector('[data-peer-error]'); error.hidden = true;
        try {
            const url = new URL(state.data.url, location.href); url.searchParams.set('peer_page', trigger.dataset.peerPage || state.data.page);
            const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('同行名单刷新失败，请稍后重试或重新登录。');
            const data = await response.json();
            if (!Array.isArray(data.rows) || data.error) throw new Error(data.error || '同行名单响应异常，请重试。');
            if (root.isConnected) { state.data = data; render(root, data); }
        } catch (problem) {
            if (root.isConnected) { error.textContent = problem.message; error.hidden = false; }
        } finally {
            state.busy = false; root.removeAttribute('aria-busy');
            root.querySelectorAll('[data-peer-refresh], [data-peer-page]').forEach(item => item.removeAttribute('aria-disabled'));
        }
    }, true);
})();
