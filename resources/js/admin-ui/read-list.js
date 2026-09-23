// Migration adapter only: Blade remains the source of permission-filtered data,
// routes, CSRF tokens and form values. It does not implement UI widgets.
export function hiddenFields(form) {
    return form ? [...form.querySelectorAll('input[type="hidden"][name]')].map(input => [input.name, input.value]) : [];
}

export function formEndpoint(form) {
    // A named input such as name="action" shadows HTMLFormElement.action in browsers.
    return form ? new URL(form.getAttribute('action') || form.ownerDocument.URL, form.baseURI).href : '';
}

export function readAction(button, { bulk = false } = {}) {
    const confirm = button.getAttribute('onclick')?.match(/^return confirm\('([\s\S]*)'\);?$/)?.[1] ?? '';
    const form = button.form;
    return {
        label: button.textContent.trim().replace(/\s*→$/, ''),
        tone: button.classList.contains('admin-table-action--danger') || button.classList.contains('is-danger') ? 'danger' : button.classList.contains('admin-table-action--neutral') ? '' : 'primary',
        confirm, bulk, disabled: button.disabled,
        opens: button.dataset.windowOpen || '',
        detail: button.hasAttribute('data-client-trace-detail') ? { ...button.dataset } : null,
        userDetail: button.hasAttribute('data-user-detail') ? { ...button.dataset } : null,
        userBulk: button.hasAttribute('data-user-bulk-open'),
        method: form?.getAttribute('method') || 'post', action: formEndpoint(form),
        fields: hiddenFields(form), name: button.name, value: button.value,
    };
}

const contentTags = new Set(['strong', 'small', 'code', 'span', 'b', 'em', 'i', 'br', 'p', 'div', 'time']);
export function contentText(nodes) {
    return nodes.map(node => node.text ?? node.tag ?? node.action?.label ?? contentText(node.children || [])).join(' ').replace(/\s+/g, ' ').trim();
}

export function readContent(parent) {
    return [...parent.childNodes].flatMap(node => {
        if (node.nodeType === 3) return [{ text: node.textContent }];
        if (node.nodeType !== 1 || ['SCRIPT', 'INPUT', 'STYLE'].includes(node.tagName)) return [];
        if (node.tagName === 'BUTTON') return [{ action: readAction(node) }];
        if (node.classList.contains('status-tag')) return [{ tag: node.textContent.trim(), tone: node.classList.contains('status-active') ? 'success' : node.classList.contains('status-revoked') ? 'danger' : node.classList.contains('status-expired') ? 'info' : 'warning' }];
        if (node.classList.contains('log-result') || node.classList.contains('admin-type-tag') || (node.tagName === 'EM' && parent.classList.contains('client-trace-device'))) {
            return [{ tag: node.textContent.trim(), tone: node.classList.contains('is-danger') ? 'danger' : node.classList.contains('is-success') || node.classList.contains('is-match') ? 'success' : node.classList.contains('is-muted') || node.classList.contains('is-neutral') ? 'info' : node.tagName === 'EM' ? 'warning' : 'primary' }];
        }
        const tag = node.tagName.toLowerCase();
        return [{ element: contentTags.has(tag) ? tag : 'span', deviceLine: parent.classList.contains('client-trace-device') && tag === 'span', children: readContent(node) }];
    });
}

export function readAdminList(root) {
    const shell = root.querySelector('.admin-table-shell, .bsphp-user-panel');
    const table = shell.querySelector('table');
    const headers = [...table.tHead.rows[0].cells];
    const rows = [...table.tBodies[0].rows].filter(row => !row.querySelector('.empty-cell, .bsphp-table-empty')).map((row, index) => {
        const checkbox = row.querySelector('[data-card-select]');
        return {
            id: checkbox?.value ?? String(index), selectionName: checkbox?.name,
            disabled: checkbox?.disabled ?? false,
            cells: [...row.cells].map(cell => {
                const content = readContent(cell);
                return { content, text: contentText(content), mono: !!cell.querySelector(':scope > code'), ellipsis: cell.hasAttribute('data-cell-ellipsis') };
            }),
        };
    });
    const filter = shell.querySelector('.admin-filter-bar');
    const fields = filter ? [...filter.querySelectorAll('.admin-filter-bar__item')].map((item, index) => {
        const input = item.querySelector('select, input:not([type="hidden"])');
        return {
            key: input.name || `display_${index}`, name: input.name,
            label: item.querySelector('strong').textContent.replace(/[：:]$/, ''),
            value: input.value, placeholder: input.getAttribute('placeholder') || '',
            options: input.tagName === 'SELECT' ? [...input.options].map(option => ({ label: option.textContent, value: option.value, disabled: option.disabled })) : null,
        };
    }) : [];
    const pager = shell.querySelector('[data-total]');
    const addDialog = root.querySelector('#add-risk-block');
    const addForm = addDialog?.querySelector('form');
    const metrics = root.querySelector('.risk-center-metrics');
    const metricValues = metrics ? [...metrics.querySelectorAll('b')].map(element => Number(element.textContent)) : [];
    return {
        title: shell.querySelector('h2').textContent,
        subtitle: shell.querySelector('.admin-table-shell-header small, .bsphp-mode-chip')?.textContent || '',
        metrics: metricValues.length ? [{ label: '近 24 小时', value: metricValues[0], suffix: '次' }, { label: '封控设备', value: metricValues[1], suffix: '台' }] : [],
        columns: headers.map((cell, index) => {
            const selection = !!cell.querySelector('[data-card-select-all]');
            const action = cell.classList.contains('admin-action-column') || cell.classList.contains('bsphp-user-action-column');
            const detail = cell.classList.contains('admin-detail-column');
            const label = cell.textContent.trim();
            const widths = { '类型': 80, '时间': 156, '时间 / IP': 224, '封控对象': 220, '关联': 240, '日志 IP': 240, '原因': 260, '真实原因': 220, '客户端提示': 132, '设备': 200, '软件 / 卡密': 190, '设备对比': 250, '响应摘要': 180, '结果': 120, '记录时间': 178, '登录ip': 220, '信息': 260 };
            const isRisk = root.dataset.listKind === 'risk';
            if (isRisk) Object.assign(widths, { '类型': 72, '封控对象': 200, '关联': 210, '日志 IP': 210, '原因': 190, '时间': 140 });
            if (root.dataset.listKind === 'users') Object.assign(widths, { '充值卡号(激活码)': 230, '用户名': 160, '状态': 86, '账号多开': 100, '机器用量': 100, '到期时间': 170, '有效期': 144, '最后在线': 150, '绑定特征': 180, '备注': 180 });
            return { key: index, label, selection, action, detail, width: selection ? 48 : action ? (isRisk ? 100 : 116) : detail ? 128 : undefined, minWidth: widths[label] || 120 };
        }),
        rows,
        emptyText: table.querySelector('.empty-cell, .bsphp-table-empty')?.textContent.trim() || '暂无数据',
        headingActions: [...shell.querySelectorAll('.admin-table-shell-heading-actions button')].map(button => readAction(button)),
        actions: [...shell.querySelectorAll('.admin-data-table-toolbar > div:not(.admin-table-utility-tools) button')].map(button => readAction(button, { bulk: true })),
        filter: { fields, action: filter?.action, hidden: hiddenFields(filter) },
        pagination: { page: Number(pager?.dataset.page || 1), total: Number(pager?.dataset.total || rows.length), perPage: Number(pager?.dataset.perPage || 20) },
        add: addForm ? {
            title: '添加封禁', note: addForm.querySelector('.administrator-create-note')?.textContent || '',
            action: addForm.action, fields: hiddenFields(addForm), autoOpen: addDialog.hasAttribute('data-window-auto-open'),
            inputs: [...addForm.querySelectorAll('.administrator-create-row')].map(row => {
                const input = row.querySelector('input');
                return { name: input.name, value: input.value, label: row.querySelector('strong').textContent, help: input.dataset.error ? '' : row.querySelector('small')?.textContent, error: input.dataset.error || '', placeholder: input.placeholder, maxlength: input.maxLength > 0 ? input.maxLength : undefined };
            }),
        } : null,
    };
}

export function submissionFields(action, selection = []) {
    const fields = [...action.fields];
    if (action.name) fields.push([action.name, action.value]);
    if (action.bulk) selection.forEach(row => { if (row.selectionName) fields.push([row.selectionName, row.id]); });
    return fields;
}

export function decodePayload(value) {
    try {
        return JSON.stringify(JSON.parse(new TextDecoder().decode(Uint8Array.from(atob(value || ''), c => c.charCodeAt(0)))), null, 2);
    } catch { return '{}'; }
}

export function csvCell(value) {
    const text = String(value ?? '').trim();
    return `"${(/^[=+\-@\t\r]/.test(text) ? "'" : '') + text.replaceAll('"', '""')}"`;
}
