(() => {
    const KEY = '0ffa2324abc6627a47ab306548d314fb039f51455d91620c952f379e7e36c6c2';
    const normalize = card => card.replace(/^[ \t\r\n]+|[ \t\r\n]+$/g, '');

    async function unlockPassword(card) {
        card = normalize(card);
        const encoder = new TextEncoder();
        const bytes = encoder.encode(card);
        if (!bytes.length || bytes.length >= 512 || card.includes('\0')) {
            throw new Error('卡密应为 1～511 字节，且不含空字符');
        }
        const key = await crypto.subtle.importKey('raw', encoder.encode(KEY), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
        const digest = await crypto.subtle.sign("HMAC", key, encoder.encode(`mtx-tracking-unlock:v1:${card}`));

        return String(new DataView(digest).getUint32(0, false) % 100000000).padStart(8, '0');
    }

    const card = document.getElementById('card');
    const code = document.getElementById('code');
    const error = document.getElementById('error');
    const show = document.getElementById('show');
    const copy = document.getElementById('copy');

    card.addEventListener('input', () => {
        code.textContent = '';
        error.textContent = '';
    });

    show.addEventListener('click', async () => {
        code.textContent = '';
        error.textContent = '';
        const input = card.value;
        try {
            const value = await unlockPassword(input);
            if (card.value === input) code.textContent = value;
        } catch (exception) {
            error.textContent = exception.message;
        }
    });

    card.addEventListener('keydown', event => {
        if (event.key === 'Enter') show.click();
    });

    copy.addEventListener('click', async () => {
        if (!code.textContent) return;
        try {
            await navigator.clipboard.writeText(code.textContent);
            error.textContent = '已复制';
        } catch {
            error.textContent = '请选中上方密码进行复制';
        }
    });
})();
