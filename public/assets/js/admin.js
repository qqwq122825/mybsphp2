(() => {
    'use strict';

    const body = document.body;
    const mobileToggle = document.querySelector('[data-sidebar-toggle]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const collapse = document.querySelector('[data-sidebar-collapse]');
    const refresh = document.querySelector('[data-page-refresh]');

    const setMobileSidebar = (open) => {
        body.classList.toggle('sidebar-open', open);
        mobileToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    mobileToggle?.addEventListener('click', () => {
        setMobileSidebar(!body.classList.contains('sidebar-open'));
    });
    overlay?.addEventListener('click', () => setMobileSidebar(false));

    collapse?.addEventListener('click', () => {
        body.classList.toggle('sidebar-collapsed');
    });

    refresh?.addEventListener('click', () => window.location.reload());

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMobileSidebar(false);
        }
    });
})();
