import { formEndpoint, hiddenFields } from './read-list.js';
import { readControl, readErrors } from './read-software.js';

export function readUserDialogs(root) {
    const detail = root.querySelector('[data-user-detail-form]');
    const bulk = root.querySelector('.bsphp-user-table-wrap');
    let old = {};
    try { old = JSON.parse(root.dataset.userOld || '{}'); } catch { /* No saved input. */ }
    return {
        errors: readErrors(root), old,
        detail: { fields: hiddenFields(detail), inputs: [...detail.querySelectorAll('.bsphp-user-detail-fields > label')].map(label => ({ label: label.querySelector('span').textContent.trim(), help: label.querySelector('small')?.textContent.trim(), controls: [readControl(label.querySelector('input, textarea'))] })) },
        bulk: { action: formEndpoint(bulk), method: bulk.getAttribute('method') || 'post', fields: hiddenFields(bulk), options: [...bulk.querySelectorAll('input[type="radio"][name="action"]')].map(input => ({ value: input.value, label: input.nextElementSibling.textContent })) },
    };
}

export function userDetailModel(detail) {
    return { expires_at: detail.expires || '', bound_device: detail.binding || '', account_concurrency_limit: Number(detail.accountLimit || 0), machine_concurrency_limit: Number(detail.machineLimit || 0), note: detail.note || '' };
}

export function userBulkFields(bulk, selected, action, value) {
    return [...bulk.fields.filter(([key]) => !['id_list', 'action', 'action_value'].includes(key)), ['id_list', selected.map(row => row.id).join(',')], ['action', action], ['action_value', value ?? ''], ['_ui_bulk', '1']];
}
