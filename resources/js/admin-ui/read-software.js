import { hiddenFields } from './read-list.js';

const text = (root, selector) => root.querySelector(selector)?.textContent.trim() || '';
export function readLink(link) {
    return link ? { label: link.textContent.trim().replace(/^[＋⚙◇▣☷]\s*/, ''), href: link.href, workspace: link.hasAttribute('data-workspace-open'), title: link.dataset.workspaceTitle } : null;
}
export function readErrors(root) {
    try { return JSON.parse(root.dataset.formErrors || '{}'); } catch { return {}; }
}
export function readSoftwareList(root) {
    const pager = root.querySelector('[data-total]');
    return {
        title: text(root, 'h1'), subtitle: text(root, '.page-heading > div > p:last-child'),
        create: readLink(root.querySelector('.page-heading > a')),
        metrics: [...root.querySelectorAll('.metric-card')].map(card => ({ label: text(card, 'span'), value: Number(text(card, 'strong')), help: text(card, 'small') })),
        applications: [...root.querySelectorAll('.software-card')].map(card => {
            const trigger = card.querySelector('[data-window-open]');
            const dialog = trigger ? root.querySelector(`[id="${trigger.dataset.windowOpen}"]`) : null;
            const form = dialog?.querySelector('form');
            return {
                name: text(card, 'h2'), code: text(card, '.software-card-title p strong'),
                status: text(card, '.status-tag'), active: !!card.querySelector('.status-active'),
                mode: text(card, '.software-mode-line strong'),
                stats: [...card.querySelectorAll('.software-stats > div')].map(stat => ({ label: text(stat, 'span'), value: Number(text(stat, 'strong')) })),
                links: [...card.querySelectorAll('.software-card-actions a')].map(link => ({ ...readLink(link), tone: link.classList.contains('software-action-primary') ? 'primary' : link.classList.contains('software-action-warn') ? 'warning' : '' })),
                deletion: form ? { action: form.action, method: form.method, fields: hiddenFields(form), options: [...form.querySelectorAll('.application-delete-option')].map(option => ({ label: text(option, 'strong'), help: text(option, 'small'), name: option.querySelector('input').name })) } : null,
            };
        }),
        pagination: { page: Number(pager?.dataset.page || 1), perPage: Number(pager?.dataset.perPage || 20), total: Number(pager?.dataset.total || 0) },
    };
}

export function readControl(input, index = 0) {
    return {
        key: input.name || `readonly_${index}`, name: input.name,
        type: input.tagName === 'SELECT' ? 'select' : input.tagName === 'TEXTAREA' ? 'textarea' : input.type,
        value: input.type === 'number' && input.value !== '' ? Number(input.value) : input.value,
        required: input.required, readonly: input.readOnly, disabled: input.disabled,
        min: input.hasAttribute('min') ? Number(input.min) : undefined,
        max: input.hasAttribute('max') ? Number(input.max) : undefined,
        maxlength: input.maxLength > 0 ? input.maxLength : undefined,
        rows: input.rows, placeholder: input.placeholder, pattern: input.pattern,
        autocomplete: input.autocomplete, inputmode: input.inputMode,
        options: input.tagName === 'SELECT' ? [...input.options].map(option => ({ value: option.value, label: option.textContent, disabled: option.disabled })) : null,
    };
}

export function formValues(fields) {
    return Object.fromEntries(fields.flatMap(field => field.controls.map(control => [control.key, control.value])));
}

export function formSubmission(form, model) {
    return [...form.fields, ...form.groups.flatMap(group => group.fields.flatMap(field => field.controls.filter(control => control.name && !control.disabled).map(control => [control.name, model[control.key] ?? ''])))];
}

export function deleteSubmission(deletion, selected) {
    // Deleting the application requires its dependents; unchecked options are omitted.
    const names = selected.includes('delete_application') ? deletion.options.map(option => option.name) : selected;
    return [...deletion.fields, ...deletion.options.filter(option => names.includes(option.name)).map(option => [option.name, '1'])];
}

export function readSoftwareForm(root) {
    const form = root.querySelector('form');
    const editing = !!root.querySelector('[data-config-form]');
    let index = 0;
    const groups = [...form.querySelectorAll('.form-panel')].map(section => ({
        title: text(section, 'h2'), tab: section.closest('[data-config-panel]')?.dataset.configPanel || 'basic',
        fields: [...section.querySelectorAll('label.software-basic-row, label.field')].filter(label => label.querySelector('input:not([type="hidden"]), select, textarea')).map(label => ({
            label: text(label, ':scope > strong, :scope > span'), help: text(label, ':scope > small'),
            certificate: !!label.closest('.certificate-panel'),
            controls: [...label.querySelectorAll('input:not([type="hidden"]), select, textarea')].map(input => readControl(input, index++)),
            copy: label.querySelector('[data-copy]')?.dataset.copy,
        })),
        connections: [...section.querySelectorAll('.connection-row')].map(row => ({ label: text(row, 'span'), value: text(row, 'strong, code'), copy: row.querySelector('[data-copy]')?.dataset.copy, help: text(row, 'small') })),
        encryption: text(section, '.encryption-mode-value'),
    }));
    return {
        editing, title: text(root, 'h1'), subtitle: text(root, '.software-settings-heading p, .page-heading > div > p:last-child'),
        back: readLink(root.querySelector('.page-heading > a')),
        action: form.action, method: form.method, fields: hiddenFields(form), groups,
        errors: readErrors(root), activeTab: root.querySelector('[data-default-config-tab]')?.dataset.defaultConfigTab || 'basic',
        proxy: JSON.parse(root.querySelector('[data-proxy-link-config]')?.textContent || 'null'),
        peerPresence: JSON.parse(root.querySelector('[data-peer-presence-config]')?.textContent || 'null'),
        tabs: editing ? [...root.querySelectorAll('[data-config-tab]')].map(tab => ({ name: tab.dataset.configTab, label: text(tab, 'strong') })) : [{ name: 'basic', label: '软件基本信息' }],
        submitLabel: text(form, 'button[type="submit"]'), submitHelp: text(form, '.form-submit-bar > span'),
    };
}
