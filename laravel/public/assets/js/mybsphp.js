(() => {
    const errorSummary = document.querySelector('[data-error-summary]');
    if (errorSummary instanceof HTMLElement) {
        errorSummary.focus();
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle || '');
            if (!(input instanceof HTMLInputElement)) return;
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.textContent = visible ? '显示' : '隐藏';
            button.setAttribute('aria-pressed', visible ? 'false' : 'true');
        });
    });

    const secondaryToggle = document.querySelector('[data-secondary-toggle]');
    const secondaryFields = document.querySelector('[data-secondary-fields]');
    const syncSecondary = () => {
        if (!(secondaryToggle instanceof HTMLInputElement) || !(secondaryFields instanceof HTMLElement)) return;
        secondaryFields.hidden = !secondaryToggle.checked;
        secondaryFields.querySelectorAll('input').forEach((input) => {
            input.disabled = !secondaryToggle.checked;
            input.required = secondaryToggle.checked;
        });
    };
    secondaryToggle?.addEventListener('change', syncSecondary);
    syncSecondary();

    document.querySelectorAll('[data-submit-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (!(button instanceof HTMLButtonElement)) return;
            const label = button.dataset.submitLabel || '正在处理…';
            window.setTimeout(() => {
                button.disabled = true;
                button.textContent = label;
            }, 0);
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copy || '';
            await navigator.clipboard.writeText(value);
            const previous = button.textContent;
            button.textContent = '已复制';
            window.setTimeout(() => { button.textContent = previous; }, 1200);
        });
    });

    const navToggle = document.querySelector('[data-nav-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const setNav = (open) => {
        sidebar?.classList.toggle('is-open', open);
        overlay?.classList.toggle('is-open', open);
        navToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    navToggle?.addEventListener('click', () => setNav(!sidebar?.classList.contains('is-open')));
    overlay?.addEventListener('click', () => setNav(false));
})();
