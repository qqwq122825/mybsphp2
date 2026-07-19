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

    const workspaceSidebarToggles = [...document.querySelectorAll('[data-workspace-sidebar-toggle]')];
    workspaceSidebarToggles.forEach((button) => {
        button.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 860px)').matches) {
                setNav(true);
                return;
            }
            const collapsed = document.body.classList.toggle('is-sidebar-collapsed');
            workspaceSidebarToggles.forEach((toggle) => {
                toggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
            });
        });
    });

    document.querySelectorAll('[data-page-refresh]').forEach((button) => {
        button.addEventListener('click', () => window.location.reload());
    });

    const quickEntry = document.querySelector('[data-quick-entry]');
    document.addEventListener('click', (event) => {
        if (!(quickEntry instanceof HTMLDetailsElement) || !quickEntry.open) return;
        if (event.target instanceof Node && !quickEntry.contains(event.target)) quickEntry.open = false;
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && quickEntry instanceof HTMLDetailsElement) quickEntry.open = false;
    });

    const configTabs = [...document.querySelectorAll('[data-config-tab]')];
    const configPanels = [...document.querySelectorAll('[data-config-panel]')];
    const configForm = document.querySelector('[data-config-form]');
    const activateConfigTab = (name, updateHash = true) => {
        if (!configTabs.some((tab) => tab.dataset.configTab === name)) return;
        configTabs.forEach((tab) => {
            const active = tab.dataset.configTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active ? '0' : '-1');
        });
        configPanels.forEach((panel) => {
            panel.hidden = panel.dataset.configPanel !== name;
        });
        if (updateHash) history.replaceState(null, '', `#${name}`);
    };

    if (configForm instanceof HTMLElement && configTabs.length > 0) {
        const hashTab = window.location.hash.replace('#', '');
        const defaultTab = configTabs.some((tab) => tab.dataset.configTab === hashTab)
            ? hashTab
            : (configForm.dataset.defaultConfigTab || 'basic');
        configTabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activateConfigTab(tab.dataset.configTab || 'basic'));
            tab.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                const direction = event.key === 'ArrowRight' ? 1 : -1;
                const next = configTabs[(index + direction + configTabs.length) % configTabs.length];
                activateConfigTab(next.dataset.configTab || 'basic');
                next.focus();
            });
        });
        activateConfigTab(defaultTab, false);
    }

    const workspaceContent = document.querySelector('[data-workspace-content]');
    const workspaceTabs = document.querySelector('[data-workspace-tabs]');
    if (workspaceContent instanceof HTMLElement && workspaceTabs instanceof HTMLElement) {
        const baseContent = workspaceContent.innerHTML;
        const baseTitle = document.title;
        const baseTabs = [...workspaceTabs.children];
        const initiallyActiveTab = baseTabs.find((tab) => tab.classList.contains('is-active'));
        const temporaryTabs = new Map();
        let activeKey = null;

        const setBaseTabState = (active) => {
            baseTabs.forEach((tab) => tab.classList.remove('is-active'));
            if (active && initiallyActiveTab instanceof HTMLElement) {
                initiallyActiveTab.classList.add('is-active');
            }
        };

        const renderWorkspace = (key) => {
            const tab = temporaryTabs.get(key);
            if (!tab) return;
            activeKey = key;
            setBaseTabState(false);
            temporaryTabs.forEach((item, itemKey) => {
                item.element.classList.toggle('is-active', itemKey === key);
            });
            workspaceContent.innerHTML = tab.content;
            document.title = tab.documentTitle || baseTitle;
            tab.element.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            window.scrollTo({ top: 0, behavior: 'auto' });
        };

        const renderBaseWorkspace = () => {
            activeKey = null;
            temporaryTabs.forEach((item) => item.element.classList.remove('is-active'));
            setBaseTabState(true);
            workspaceContent.innerHTML = baseContent;
            document.title = baseTitle;
            window.scrollTo({ top: 0, behavior: 'auto' });
        };

        const parseWorkspaceResponse = async (response) => {
            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const incomingContent = parsed.querySelector('[data-workspace-content]');
            if (!(incomingContent instanceof HTMLElement)) {
                throw new Error('工作页内容格式错误');
            }
            return {
                content: incomingContent.innerHTML,
                documentTitle: parsed.title || baseTitle,
                url: response.url,
            };
        };

        const requestWorkspace = async (url, options = {}) => {
            workspaceContent.classList.add('is-workspace-loading');
            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers: {
                        Accept: 'text/html',
                        'X-Workspace-Request': '1',
                        ...(activeKey && temporaryTabs.get(activeKey)
                            ? { 'X-Workspace-Url': temporaryTabs.get(activeKey).url }
                            : {}),
                        ...(options.headers || {}),
                    },
                });
                if (!response.ok) throw new Error(`工作页请求失败：${response.status}`);
                return await parseWorkspaceResponse(response);
            } finally {
                workspaceContent.classList.remove('is-workspace-loading');
            }
        };

        const addTemporaryTab = (key, title, page) => {
            const existing = temporaryTabs.get(key);
            if (existing) {
                existing.content = page.content;
                existing.documentTitle = page.documentTitle;
                existing.url = page.url || existing.url;
                renderWorkspace(key);
                return;
            }

            const element = document.createElement('button');
            element.type = 'button';
            element.className = 'workspace-tab';
            element.dataset.workspaceTab = key;
            element.innerHTML = '<span></span><i data-workspace-tab-close aria-label="关闭标签" title="关闭标签">×</i>';
            element.querySelector('span').textContent = title;
            workspaceTabs.append(element);
            temporaryTabs.set(key, {
                element,
                title,
                content: page.content,
                documentTitle: page.documentTitle,
                url: page.url || key,
            });
            renderWorkspace(key);
        };

        document.addEventListener('click', async (event) => {
            const closeButton = event.target instanceof Element
                ? event.target.closest('[data-workspace-tab-close]')
                : null;
            if (closeButton) {
                const tabElement = closeButton.closest('[data-workspace-tab]');
                const key = tabElement?.dataset.workspaceTab;
                if (!key || !temporaryTabs.has(key)) return;
                event.preventDefault();
                temporaryTabs.get(key).element.remove();
                temporaryTabs.delete(key);
                if (activeKey === key) {
                    const remainingKeys = [...temporaryTabs.keys()];
                    if (remainingKeys.length > 0) renderWorkspace(remainingKeys.at(-1));
                    else renderBaseWorkspace();
                }
                return;
            }

            const tabButton = event.target instanceof Element
                ? event.target.closest('[data-workspace-tab]')
                : null;
            if (tabButton) {
                const key = tabButton.dataset.workspaceTab;
                if (key && temporaryTabs.has(key)) {
                    event.preventDefault();
                    renderWorkspace(key);
                }
                return;
            }

            const link = event.target instanceof Element
                ? event.target.closest('a[data-workspace-open]')
                : null;
            if (!(link instanceof HTMLAnchorElement)) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            const key = new URL(link.href, window.location.href).href;
            const title = link.dataset.workspaceTitle || link.textContent.trim();
            try {
                const page = await requestWorkspace(key);
                addTemporaryTab(key, title, page);
            } catch (error) {
                window.alert(error instanceof Error ? error.message : '工作页加载失败');
            }
        });

        document.addEventListener('submit', async (event) => {
            if (!activeKey || !(event.target instanceof HTMLFormElement)) return;
            if (!workspaceContent.contains(event.target) || event.target.dataset.workspaceNative !== undefined) return;
            event.preventDefault();
            const form = event.target;
            if (!form.reportValidity()) return;
            const submissionKey = activeKey;
            const tab = temporaryTabs.get(submissionKey);
            if (!tab) return;
            const method = (form.method || 'get').toUpperCase();
            const formData = new FormData(form);
            let requestUrl = form.action || tab.url;
            const options = { method };
            if (method === 'GET') {
                const targetUrl = new URL(requestUrl, window.location.href);
                formData.forEach((value, name) => targetUrl.searchParams.append(name, String(value)));
                requestUrl = targetUrl.href;
            } else {
                options.body = formData;
            }

            try {
                const page = await requestWorkspace(requestUrl, options);
                tab.content = page.content;
                tab.documentTitle = page.documentTitle;
                tab.url = page.url || tab.url;
                if (activeKey === submissionKey) renderWorkspace(submissionKey);
            } catch (error) {
                window.alert(error instanceof Error ? error.message : '表单提交失败');
            }
        });
    }
})();
