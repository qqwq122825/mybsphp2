import { ElMessage, ElMessageBox } from 'element-plus';

// Use the original Blade endpoint and CSRF/method fields; business logic stays server-side.
export async function submitForm(action, fields, { preview = false, confirm = '', tone = 'info' } = {}) {
    if (confirm) {
        try {
            await ElMessageBox.confirm(confirm, '确认操作', { type: tone, confirmButtonText: '确认', cancelButtonText: '取消', closeOnClickModal: false });
        } catch { return false; }
    }
    if (preview) {
        ElMessage.success('组件交互预览完成，业务数据保持原状');
        return true;
    }
    const url = new URL(action.action, window.location.href);
    if (url.origin !== window.location.origin) {
        ElMessage.error('提交地址异常，请刷新页面后重试');
        return false;
    }
    let complete;
    const result = new Promise(resolve => { complete = resolve; });
    const event = new CustomEvent('workspace:submit', { cancelable: true, detail: { action: url.href, method: action.method || 'post', fields, complete } });
    if (!document.dispatchEvent(event)) {
        const response = await result;
        if (!response.ok) ElMessage.error(response.message || '表单提交失败');
        return response.ok;
    }
    const form = document.createElement('form');
    form.method = action.method || 'post';
    form.action = url.href;
    fields.forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value ?? '';
        form.append(input);
    });
    document.body.append(form);
    form.submit();
    return true;
}

export async function copyText(value) {
    try { await navigator.clipboard.writeText(value); ElMessage.success('已复制'); }
    catch { ElMessage.warning('请选中内容复制'); }
}
