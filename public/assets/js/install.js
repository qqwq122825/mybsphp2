(() => {
    'use strict';

    const errorSummary = document.querySelector('[data-error-summary]');
    if (errorSummary) {
        errorSummary.focus();
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle || '');
        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? '隐藏密码' : '显示密码');

            const label = button.querySelector('span');
            if (label) {
                label.textContent = show ? '隐藏' : '显示';
            }
            input.focus({ preventScroll: true });
        });
    });

    const password = document.querySelector('[data-password-strength]');
    const meter = document.querySelector('[data-password-meter]');
    const strength = document.getElementById('password_strength');
    if (password && meter && strength) {
        const updateStrength = () => {
            const value = password.value;
            let score = 0;
            score += value.length >= 12 ? 1 : 0;
            score += value.length >= 16 ? 1 : 0;
            score += /[a-z]/.test(value) && /[A-Z]/.test(value) ? 1 : 0;
            score += /\d/.test(value) ? 1 : 0;
            score += /[^A-Za-z0-9]/.test(value) ? 1 : 0;

            const levels = [
                { width: '0%', color: '#c83b4f', text: '' },
                { width: '20%', color: '#c83b4f', text: '密码强度：较弱' },
                { width: '40%', color: '#d87919', text: '密码强度：一般' },
                { width: '60%', color: '#d19a12', text: '密码强度：良好' },
                { width: '80%', color: '#3a9b78', text: '密码强度：强' },
                { width: '100%', color: '#16875f', text: '密码强度：很强' },
            ];
            const level = levels[value.length === 0 ? 0 : Math.max(1, score)];
            meter.style.width = level.width;
            meter.style.backgroundColor = level.color;
            strength.textContent = level.text;
        };

        password.addEventListener('input', updateStrength);
        updateStrength();
    }

    document.querySelectorAll('[data-password-confirm]').forEach((confirmation) => {
        const source = document.getElementById(confirmation.dataset.passwordConfirm || '');
        if (!source) {
            return;
        }

        const validate = () => {
            confirmation.setCustomValidity(
                confirmation.value !== '' && confirmation.value !== source.value
                    ? '两次输入的密码不一致'
                    : ''
            );
        };
        confirmation.addEventListener('input', validate);
        source.addEventListener('input', validate);
    });

    document.querySelectorAll('[data-admin-path]').forEach((input) => {
        input.addEventListener('input', () => {
            const cursor = input.selectionStart;
            input.value = input.value.replace(/^\/+/, '').replace(/[^A-Za-z0-9_-]/g, '');
            if (cursor !== null) {
                input.setSelectionRange(Math.min(cursor, input.value.length), Math.min(cursor, input.value.length));
            }
        });
    });

    document.querySelectorAll('[data-install-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                return;
            }

            const submitter = event.submitter;
            if (!(submitter instanceof HTMLButtonElement)) {
                return;
            }

            const originalText = submitter.textContent;
            window.requestAnimationFrame(() => {
                submitter.disabled = true;
                submitter.classList.add('is-loading');
                submitter.setAttribute('aria-busy', 'true');
                submitter.textContent = submitter.value === 'recheck' ? '检测中…' : '处理中…';
            });

            window.setTimeout(() => {
                submitter.disabled = false;
                submitter.classList.remove('is-loading');
                submitter.removeAttribute('aria-busy');
                submitter.textContent = originalText;
            }, 12000);
        });
    });
})();
