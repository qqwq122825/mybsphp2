import { formEndpoint, hiddenFields, readAction, readContent, contentText } from '../admin-ui/read-list.js';
import { readControl, readErrors } from '../admin-ui/read-software.js';

const text = (root, selector) => root.querySelector(selector)?.textContent.trim() || '';
const formData = form => ({ action: formEndpoint(form), method: form?.getAttribute('method') || 'post', fields: hiddenFields(form) });
export const purchaseTotal = (price, quantity) => Number.isSafeInteger(price) && Number.isInteger(quantity) && price >= 0 && quantity >= 1 && quantity <= 500 && Number.isSafeInteger(price * quantity) ? price * quantity : 0;
export function readAgent(root) {
    const login = root.hasAttribute('data-agent-login');
    const source = root.querySelector('.agent-main') || root;
    const purchaseForm = root.querySelector('[data-agent-purchase-form]');
    const loginForm = root.querySelector('.agent-login-form');
    const form = purchaseForm || loginForm;
    const table = source.querySelector('table');
    const pager = root.querySelector('[data-agent-pagination]');
    const filter = root.querySelector('.agent-card-filter, .admin-filter-bar');
    const headers = table ? [...table.tHead.rows[0].cells] : [];
    const controls = form ? [...form.querySelectorAll(':scope > label')].map((label, index) => ({ label: text(label, ':scope > strong, :scope > span'), help: text(label, 'small'), controls: [readControl(label.querySelector('input,select,textarea'), index)] })) : [];
    const prices = purchaseForm ? Object.fromEntries([...purchaseForm.querySelectorAll('option[data-price]')].map(option => [option.value, Number(option.dataset.price)])) : {};
    const modal = root.querySelector('[data-agent-delete-modal], [data-agent-unbind-modal]');
    return {
        login, title: text(source, 'h1'), appName: root.dataset.appName,
        account: text(root, '.agent-account-menu > span'),
        navigation: [...root.querySelectorAll('.agent-nav > a')].map(link => ({ label: text(link, 'span'), href: link.href, active: link.classList.contains('is-active') })),
        logout: formData(root.querySelector('.agent-account-menu form')),
        errors: readErrors(root), success: root.dataset.success || '',
        kind: login ? 'login' : purchaseForm ? 'purchase' : table ? 'table' : 'dashboard',
        announcement: text(source, '.agent-announcement-panel > div'),
        metrics: [...source.querySelectorAll('.agent-metric-card')].map(card => ({ label: text(card, 'header strong'), value: Number(text(card, 'b').replaceAll(',', '')), unit: text(card, 'small'), money: card.classList.contains('is-balance') })),
        form: form ? { ...formData(form), controls, prices, captcha: text(form, '.agent-captcha-code'), submit: text(form, 'button[type="submit"]') } : null,
        balance: Number(root.dataset.balance || 0),
        result: text(source, '[data-generated-content]'), resultDescription: text(source, '.agent-generated-result p'),
        table: table ? {
            columns: headers.map((header, index) => ({ key: index, label: header.textContent.trim(), star: header.classList.contains('agent-star-cell'), action: header.textContent.trim() === '操作' })),
            rows: [...table.tBodies[0].rows].filter(row => !row.querySelector('.empty-cell, .bsphp-table-empty')).map((row,index) => ({
                id: String(index), recordId: row.cells[headers.findIndex(header => header.textContent.trim() === '编号')]?.textContent.trim() || '', actions: [...row.querySelectorAll('.agent-row-actions button')].map(button => readAction(button)), star: row.querySelector('[data-agent-star]') ? { on: row.querySelector('[data-agent-star]').classList.contains('is-on'), url: row.querySelector('[data-agent-star]').dataset.starUrl } : null,
                cells: [...row.cells].map(cell => ({ content: readContent(cell), text: contentText(readContent(cell)), money: cell.classList.contains('agent-money-in') ? 'success' : cell.classList.contains('agent-money-out') ? 'danger' : '' })),
            })),
            empty: text(table, '.empty-cell, .bsphp-table-empty') || '暂无数据',
            page: Number(pager?.dataset.page || 1), total: Number(pager?.dataset.total || 0), perPage: Number(pager?.dataset.perPage || 20),
            keyword: filter?.querySelector('[name="keyword"]')?.value || '', filter: !!filter, multiline: filter?.querySelector('textarea') !== null && !!filter,
            action: filter ? formEndpoint(filter) : root.dataset.pageUrl,
            create: source.querySelector('.admin-data-table-toolbar a')?.href,
            financial: source.classList.contains('agent-finance-panel') || !!source.querySelector('.agent-finance-panel'),
        } : null,
        modal: modal ? { title: text(modal, 'h2'), description: text(modal, 'p'), card: text(modal, 'li'), actions: [...modal.querySelectorAll('button[type="submit"]')].map(button => readAction(button)) } : null,
        csrf: root.ownerDocument.querySelector('meta[name="csrf-token"]')?.content || form?.querySelector('[name="_token"]')?.value || '',
    };
}
