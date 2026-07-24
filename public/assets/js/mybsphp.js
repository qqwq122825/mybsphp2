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

    document.addEventListener('click', async (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-copy]') : null;
        if (!(button instanceof HTMLButtonElement)) return;
        const value = button.dataset.copy || '';
        await navigator.clipboard.writeText(value);
        const previous = button.textContent;
        button.textContent = '已复制';
        window.setTimeout(() => { button.textContent = previous; }, 1200);
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

    const showWindowDialog = (dialog) => {
        if (!(dialog instanceof HTMLDialogElement) || dialog.open) return;
        dialog.classList.remove('is-minimized');
        dialog.classList.add('is-maximized');
        if (dialog.dataset.windowScope === 'workspace') {
            dialog.show();
        } else {
            dialog.showModal();
        }
    };

    document.addEventListener('admin-window:open', (event) => {
        showWindowDialog(event.target);
    });

    const initializeConfigTabs = (scope = document, useBrowserHash = true) => {
        const configForm = scope.querySelector('[data-config-form]');
        if (!(configForm instanceof HTMLElement) || configForm.dataset.configTabsReady === '1') return;

        const configTabs = [...configForm.querySelectorAll('[data-config-tab]')];
        const configPanels = [...configForm.querySelectorAll('[data-config-panel]')];
        if (configTabs.length === 0) return;

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
            if (useBrowserHash && updateHash) history.replaceState(null, '', `#${name}`);
            configForm.dispatchEvent(new CustomEvent('workspace:config-tab', {
                bubbles: true,
                detail: {
                    name,
                    panel: configForm.querySelector(`[data-config-panel="${name}"]`),
                },
            }));
        };

        const hashTab = useBrowserHash ? window.location.hash.replace('#', '') : '';
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
        configForm.dataset.configTabsReady = '1';
        activateConfigTab(defaultTab, false);
    };

    const workspaceContent = document.querySelector('[data-workspace-content]');
    const workspaceTabs = document.querySelector('[data-workspace-tabs]');
    if (workspaceContent instanceof HTMLElement && workspaceTabs instanceof HTMLElement) {
        let baseContent = workspaceContent.innerHTML;
        let baseTitle = document.title;
        let baseTabs = [...workspaceTabs.children];
        let initiallyActiveTab = baseTabs.find((tab) => tab.classList.contains('is-active'));
        const temporaryTabs = new Map();
        const baseUrl = workspaceTabs.dataset.workspaceBaseUrl
            ? new URL(workspaceTabs.dataset.workspaceBaseUrl, window.location.href).href
            : null;
        const bootstrapUrl = workspaceTabs.dataset.workspaceBootstrapUrl
            ? new URL(workspaceTabs.dataset.workspaceBootstrapUrl, window.location.href).href
            : null;
        const bootstrapTitle = workspaceTabs.dataset.workspaceBootstrapTitle || '';
        let activeKey = null;

        const setBaseTabState = (active) => {
            baseTabs.forEach((tab) => tab.classList.remove('is-active'));
            if (active && initiallyActiveTab instanceof HTMLElement) {
                initiallyActiveTab.classList.add('is-active');
            }
        };

        const openAutomaticWindows = (scope = document) => {
            scope.querySelectorAll('[data-window-dialog][data-window-auto-open]').forEach((dialog) => {
                showWindowDialog(dialog);
            });
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
            openAutomaticWindows(workspaceContent);
            initializeConfigTabs(workspaceContent, false);
            document.title = tab.documentTitle || baseTitle;
            tab.element.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            window.scrollTo({ top: 0, behavior: 'auto' });
        };

        const renderBaseWorkspace = () => {
            activeKey = null;
            temporaryTabs.forEach((item) => item.element.classList.remove('is-active'));
            setBaseTabState(true);
            workspaceContent.innerHTML = baseContent;
            openAutomaticWindows(workspaceContent);
            initializeConfigTabs(workspaceContent, false);
            document.title = baseTitle;
            window.scrollTo({ top: 0, behavior: 'auto' });
        };

        const parseWorkspaceResponse = async (response) => {
            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const incomingContent = parsed.querySelector('[data-workspace-content]');
            const incomingTabs = parsed.querySelector('[data-workspace-tabs]');
            if (!(incomingContent instanceof HTMLElement)) {
                throw new Error('工作页内容格式错误');
            }
            return {
                content: incomingContent.innerHTML,
                tabs: incomingTabs instanceof HTMLElement ? incomingTabs.innerHTML : '',
                riskContent: parsed.querySelector('[data-risk-content]')?.innerHTML || '',
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

        const normalizeUrl = (url) => {
            const normalized = new URL(url, window.location.href);
            normalized.hash = '';
            return normalized.href;
        };

        const isBaseLink = (link) => baseUrl
            && link instanceof HTMLAnchorElement
            && normalizeUrl(link.href) === normalizeUrl(baseUrl);

        const loadEmbeddedRiskPanel = async (panel) => {
            if (!(panel instanceof HTMLElement) || panel.dataset.riskLoaded === '1') return;
            panel.classList.add('is-risk-loading');
            try {
                const page = await requestWorkspace(panel.dataset.riskUrl || '');
                if (!page.riskContent) throw new Error('封控策略内容格式错误');
                panel.innerHTML = page.riskContent;
                panel.dataset.riskLoaded = '1';
                openAutomaticWindows(panel);
            } finally {
                panel.classList.remove('is-risk-loading');
            }
        };

        document.addEventListener('workspace:config-tab', async (event) => {
            if (!(event instanceof CustomEvent) || event.detail?.name !== 'risk') return;
            const panel = event.detail.panel;
            if (!(panel instanceof HTMLElement)) return;
            try {
                await loadEmbeddedRiskPanel(panel);
            } catch (error) {
                panel.innerHTML = `<div class="risk-panel-loading">${error instanceof Error ? error.message : '封控策略加载失败'}</div>`;
            }
        });

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
            if (link instanceof HTMLAnchorElement) {
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
                return;
            }

            const baseLink = event.target instanceof Element
                ? event.target.closest('a[data-workspace-base-tab], a[data-workspace-base-link], [data-workspace-content] a')
                : null;
            if (!isBaseLink(baseLink)) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            renderBaseWorkspace();
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
            if (event.submitter instanceof HTMLButtonElement && event.submitter.name) {
                formData.set(event.submitter.name, event.submitter.value);
            }
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
                const embeddedRiskPanel = form.closest('[data-risk-panel]');
                if (embeddedRiskPanel instanceof HTMLElement) {
                    const page = await requestWorkspace(requestUrl, {
                        ...options,
                        headers: {
                            'X-Workspace-Url': embeddedRiskPanel.dataset.riskUrl || requestUrl,
                        },
                    });
                    if (!page.riskContent) throw new Error('封控策略内容格式错误');
                    embeddedRiskPanel.innerHTML = page.riskContent;
                    embeddedRiskPanel.dataset.riskLoaded = '1';
                    openAutomaticWindows(embeddedRiskPanel);
                    return;
                }
                const page = await requestWorkspace(requestUrl, options);
                tab.content = page.content;
                tab.documentTitle = page.documentTitle;
                tab.url = page.url || tab.url;
                if (activeKey === submissionKey) renderWorkspace(submissionKey);
            } catch (error) {
                window.alert(error instanceof Error ? error.message : '表单提交失败');
            }
        });

        if (bootstrapUrl && baseUrl && normalizeUrl(bootstrapUrl) !== normalizeUrl(baseUrl)) {
            const bootstrapContent = baseContent;
            const bootstrapDocumentTitle = baseTitle;
            history.replaceState(history.state, '', baseUrl);
            requestWorkspace(baseUrl)
                .then((page) => {
                    baseContent = page.content;
                    baseTitle = page.documentTitle;
                    if (page.tabs) workspaceTabs.innerHTML = page.tabs;
                    baseTabs = [...workspaceTabs.children];
                    initiallyActiveTab = baseTabs.find((tab) => tab.classList.contains('is-active'));
                    addTemporaryTab(bootstrapUrl, bootstrapTitle || bootstrapDocumentTitle, {
                        content: bootstrapContent,
                        documentTitle: bootstrapDocumentTitle,
                        url: bootstrapUrl,
                    });
                })
                .catch((error) => {
                    window.alert(error instanceof Error ? error.message : '软件管理工作区加载失败');
                });
        } else {
            initializeConfigTabs(document, true);
        }
    } else {
        initializeConfigTabs(document, true);
    }

    const cardExportPanel = (target) => target instanceof Element
        ? target.closest('[data-card-export-panel]')
        : null;
    const exportCredentials = (panel) => {
        const source = panel?.querySelector('[data-card-export-json]');
        if (!(source instanceof HTMLScriptElement)) return [];
        try {
            const rows = JSON.parse(source.textContent || '[]');
            return Array.isArray(rows) ? rows : [];
        } catch {
            return [];
        }
    };
    const formatCredentials = (rows, format) => rows.map((row, index) => {
        const code = String(row.code || '');
        const secret = String(row.secret || '');
        if (format === 'code') return code;
        if (format === 'tab') return secret === '' ? code : `${code}\t${secret}`;
        if (format === 'detail') return `${index + 1}. 卡号：${code}${secret === '' ? '' : `  密码：${secret}`}`;
        if (format === 'code_secret') return secret === '' ? code : `${code} ${secret}`;
        return secret === '' ? code : `${code}----${secret}`;
    }).join('\n');
    const setExportStatus = (panel, message) => {
        const status = panel?.querySelector('[data-card-export-status]');
        if (status instanceof HTMLElement) status.textContent = message;
    };
    const renderExportTemplate = (panel, rows, template) => rows.map((row) => template
        .replaceAll('{KAID}', String(row.code || ''))
        .replaceAll('{KAPWD}', String(row.secret || ''))
        .replaceAll('{APPSDATES}', panel.dataset.cardExportStatusText || '')
        .replaceAll('{APPTIME}', panel.dataset.cardExportTime || '')
        .replaceAll('{APPNAME}', panel.dataset.cardExportApp || '')
    ).join('\n');

    document.addEventListener('click', async (event) => {
        const panel = cardExportPanel(event.target);
        if (!(panel instanceof HTMLElement)) return;
        const output = panel.querySelector('[data-card-export-output]');
        if (!(output instanceof HTMLTextAreaElement)) return;
        const rows = exportCredentials(panel);

        const searchButton = event.target instanceof Element
            ? event.target.closest('[data-card-export-search]')
            : null;
        if (searchButton instanceof HTMLButtonElement) {
            const templateInput = panel.querySelector('[data-card-export-template]');
            const template = templateInput instanceof HTMLInputElement ? templateInput.value : '{KAID}';
            output.value = renderExportTemplate(panel, rows, template);
            setExportStatus(panel, `已按显示格式生成，共 ${rows.length} 张。`);
            return;
        }

        const formatButton = event.target instanceof Element
            ? event.target.closest('[data-card-export-format]')
            : null;
        if (formatButton instanceof HTMLButtonElement) {
            const templates = {
                code: '{KAID}',
                code_secret: '卡号:{KAID} 密码:{KAPWD}',
                standard: '卡号:{KAID} 密码:{KAPWD} 状态:{APPSDATES} 时间:{APPTIME} 软件:{APPNAME}',
                dash: '{KAID}----{KAPWD}',
            };
            const format = formatButton.dataset.cardExportFormat || 'standard';
            const template = templates[format];
            const templateInput = panel.querySelector('[data-card-export-template]');
            if (template && templateInput instanceof HTMLInputElement) {
                templateInput.value = template;
                output.value = renderExportTemplate(panel, rows, template);
            } else {
                output.value = formatCredentials(rows, format);
            }
            setExportStatus(panel, `已切换格式，共 ${rows.length} 张。`);
            return;
        }

        const copyButton = event.target instanceof Element
            ? event.target.closest('[data-card-export-copy]')
            : null;
        if (copyButton instanceof HTMLButtonElement) {
            await navigator.clipboard.writeText(output.value);
            setExportStatus(panel, `已复制 ${rows.length} 张卡。`);
            return;
        }

        const csvButton = event.target instanceof Element
            ? event.target.closest('[data-card-export-csv]')
            : null;
        if (csvButton instanceof HTMLButtonElement) {
            const escapeCsv = (value) => `"${String(value).replaceAll('"', '""')}"`;
            const csv = ['card_code,card_password', ...rows.map((row) => (
                `${escapeCsv(row.code || '')},${escapeCsv(row.secret || '')}`
            ))].join('\n');
            const url = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' }));
            const link = document.createElement('a');
            link.href = url;
            link.download = `cards-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            URL.revokeObjectURL(url);
            setExportStatus(panel, `CSV 已生成，共 ${rows.length} 张。`);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !(event.target instanceof HTMLInputElement)) return;
        if (!event.target.matches('[data-card-export-template]')) return;
        event.preventDefault();
        const panel = cardExportPanel(event.target);
        const searchButton = panel?.querySelector('[data-card-export-search]');
        if (searchButton instanceof HTMLButtonElement) searchButton.click();
    });

    document.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-delete-application-master]')) {
            if (event.target.checked) {
                event.target.closest('dialog')?.querySelectorAll('input[type="checkbox"][name^="delete_"]').forEach((input) => {
                    if (input instanceof HTMLInputElement) input.checked = true;
                });
            }
            return;
        }

        if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-card-select-all]')) return;
        const scope = event.target.closest('form') || document;
        scope.querySelectorAll('[data-card-select]').forEach((checkbox) => {
            if (checkbox instanceof HTMLInputElement) checkbox.checked = event.target.checked;
        });
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;

        if (event.target instanceof HTMLDialogElement && event.target.matches('[data-window-dialog]')) {
            if (event.target.dataset.windowScope === 'workspace') return;
            event.target.close();
            event.target.classList.remove('is-minimized');
            event.target.classList.add('is-maximized');
            return;
        }

        const windowOpenButton = event.target.closest('[data-window-open]');
        if (windowOpenButton instanceof HTMLButtonElement) {
            const dialog = document.getElementById(windowOpenButton.dataset.windowOpen || '');
            if (dialog instanceof HTMLDialogElement) {
                event.preventDefault();
                showWindowDialog(dialog);
            }
            return;
        }

        const windowAction = event.target.closest('[data-window-action]');
        if (windowAction instanceof HTMLButtonElement) {
            const dialog = windowAction.closest('[data-window-dialog]');
            if (!(dialog instanceof HTMLDialogElement)) return;
            if (windowAction.dataset.windowAction === 'close') {
                dialog.close();
                dialog.classList.remove('is-minimized');
                dialog.classList.add('is-maximized');
            } else if (windowAction.dataset.windowAction === 'minimize') {
                dialog.classList.toggle('is-minimized');
                dialog.classList.remove('is-maximized');
            } else if (windowAction.dataset.windowAction === 'maximize') {
                dialog.classList.remove('is-minimized');
                dialog.classList.toggle('is-maximized');
            }
            return;
        }

        const densityButton = event.target.closest('[data-table-density-toggle]');
        if (densityButton instanceof HTMLButtonElement) {
            densityButton.closest('.admin-data-table')?.classList.toggle('is-compact');
            return;
        }

        const exportButton = event.target.closest('[data-table-export]');
        if (exportButton instanceof HTMLButtonElement) {
            const table = exportButton.closest('.admin-data-table')?.querySelector('table');
            if (!(table instanceof HTMLTableElement)) return;
            const rows = [...table.rows].map((row) => [...row.cells]
                .filter((cell) => !cell.classList.contains('admin-select-column'))
                .map((cell) => `"${cell.innerText.trim().replaceAll('"', '""')}"`)
                .join(','));
            const blob = new Blob([`\uFEFF${rows.join('\n')}`], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `table-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            URL.revokeObjectURL(url);
            return;
        }

        const openButton = event.target.closest('[data-user-bulk-open]');
        if (openButton instanceof HTMLButtonElement) {
            const form = openButton.closest('form');
            const dialog = form?.querySelector('[data-user-bulk-dialog]');
            if (!(form instanceof HTMLFormElement) || !(dialog instanceof HTMLDialogElement)) return;
            const selected = form.querySelectorAll('[name="user_ids[]"]:checked').length;
            const selectedIds = [...form.querySelectorAll('[name="user_ids[]"]:checked')]
                .map((input) => input.value);
            const count = dialog.querySelector('[data-user-bulk-count]');
            if (count instanceof HTMLElement) count.textContent = String(selected);
            if (selected < 1) {
                window.alert('请先勾选至少一个软件用户。');
                return;
            }
            const idsInput = form.querySelector('[data-user-bulk-ids]');
            const idsDisplay = dialog.querySelector('[data-user-bulk-id-display]');
            if (idsInput instanceof HTMLInputElement) idsInput.value = selectedIds.join(',');
            if (idsDisplay instanceof HTMLInputElement) idsDisplay.value = selectedIds.join(',');
            showWindowDialog(dialog);
            return;
        }

        const closeButton = event.target.closest('[data-user-bulk-close]');
        if (closeButton) closeButton.closest('dialog')?.close();
    });

    document.querySelectorAll('[data-window-dialog]').forEach((dialog) => {
        if (!(dialog instanceof HTMLDialogElement)) return;
        new MutationObserver(() => {
            if (!dialog.open) return;
            dialog.classList.remove('is-minimized');
            dialog.classList.add('is-maximized');
        }).observe(dialog, { attributes: true, attributeFilter: ['open'] });
    });

    document.querySelectorAll('[data-window-dialog][data-window-auto-open]').forEach((dialog) => {
        showWindowDialog(dialog);
    });

    document.addEventListener('click', (event) => {
        if (event.target instanceof HTMLDialogElement
            && event.target.matches('[data-user-bulk-dialog]')
            && event.target.dataset.windowScope !== 'workspace') {
            event.target.close();
        }
    });

    document.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-user-bulk-dialog] [name="action"]')) return;
        const dialog = event.target.closest('[data-user-bulk-dialog]');
        if (!(dialog instanceof HTMLDialogElement)) return;
        const row = dialog.querySelector('[data-user-bulk-value-row]');
        const input = dialog.querySelector('[data-user-bulk-value]');
        const help = dialog.querySelector('[data-user-bulk-value-help]');
        if (!(row instanceof HTMLElement) || !(input instanceof HTMLInputElement) || !(help instanceof HTMLElement)) return;
        const hints = {
            add_time: '填写要增加的秒数',
            subtract_time: '填写要减少的秒数',
            set_expiry: '填写日期时间或 Unix 时间戳',
            bind_feature: '填写新的绑定特征',
        };
        const hint = hints[event.target.value] || '';
        row.hidden = hint === '';
        input.required = hint !== '';
        if (hint === '') input.value = '';
        help.textContent = hint;
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        const openButton = event.target.closest('[data-user-detail]');
        const dialog = document.querySelector('[data-user-detail-dialog]');
        if (openButton instanceof HTMLButtonElement && dialog instanceof HTMLDialogElement) {
            const form = dialog.querySelector('[data-user-detail-form]');
            if (!(form instanceof HTMLFormElement)) return;
            form.action = openButton.dataset.action || '';
            const values = {
                expires_at: openButton.dataset.expires || '',
                bound_device: openButton.dataset.binding || '',
                account_concurrency_limit: openButton.dataset.accountLimit || '0',
                machine_concurrency_limit: openButton.dataset.machineLimit || '0',
                note: openButton.dataset.note || '',
            };
            Object.entries(values).forEach(([name, value]) => {
                const input = form.elements.namedItem(name);
                if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) input.value = value;
            });
            const title = dialog.querySelector('[data-user-detail-title]');
            if (title instanceof HTMLElement) title.textContent = openButton.dataset.title || '编辑软件用户';
            showWindowDialog(dialog);
            return;
        }
        const closeButton = event.target.closest('[data-user-detail-close]');
        if (closeButton && dialog instanceof HTMLDialogElement) dialog.close();
    });
})();
