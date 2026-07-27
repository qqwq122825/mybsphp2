(function () {
    'use strict';

    var instances = [];
    var opened = null;

    function text(value) {
        return String(value || '').replace(/^\s+|\s+$/g, '');
    }

    function closestByClass(element, className) {
        while (element && element !== document) {
            if (element.classList && element.classList.contains(className)) return element;
            element = element.parentNode;
        }
        return null;
    }

    function emitChange(select) {
        var event;
        if (typeof Event === 'function') {
            event = new Event('change', { bubbles: true });
        } else {
            event = document.createEvent('Event');
            event.initEvent('change', true, false);
        }
        select.dispatchEvent(event);
    }

    function close(instance) {
        if (!instance) return;
        instance.wrapper.classList.remove('is-open');
        instance.trigger.setAttribute('aria-expanded', 'false');
        instance.menu.hidden = true;
        if (opened === instance) opened = null;
    }

    function closeAll(except) {
        var index;
        for (index = 0; index < instances.length; index += 1) {
            if (instances[index] !== except) close(instances[index]);
        }
    }

    function sync(instance) {
        var selected = instance.select.options[instance.select.selectedIndex];
        var index;
        instance.label.textContent = selected ? text(selected.textContent) : '';
        instance.trigger.disabled = instance.select.disabled;
        for (index = 0; index < instance.options.length; index += 1) {
            instance.options[index].button.setAttribute(
                'aria-selected',
                instance.options[index].option.value === instance.select.value ? 'true' : 'false'
            );
        }
    }

    function highlight(instance, index) {
        var itemIndex;
        if (!instance.options.length) return;
        index = Math.max(0, Math.min(index, instance.options.length - 1));
        instance.highlighted = index;
        for (itemIndex = 0; itemIndex < instance.options.length; itemIndex += 1) {
            instance.options[itemIndex].button.classList.toggle('is-highlighted', itemIndex === index);
        }
        if (instance.options[index].button.scrollIntoView) {
            instance.options[index].button.scrollIntoView(false);
        }
    }

    function open(instance) {
        closeAll(instance);
        instance.wrapper.classList.add('is-open');
        instance.trigger.setAttribute('aria-expanded', 'true');
        instance.menu.hidden = false;
        instance.highlighted = 0;
        var index;
        for (index = 0; index < instance.options.length; index += 1) {
            if (instance.options[index].option.value === instance.select.value) {
                instance.highlighted = index;
                break;
            }
        }
        highlight(instance, instance.highlighted);
        opened = instance;
    }

    function addOption(instance, option) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-option';
        button.textContent = text(option.textContent);
        button.disabled = option.disabled;
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', option.value === instance.select.value ? 'true' : 'false');
        button.addEventListener('mouseenter', function () {
            var index;
            for (index = 0; index < instance.options.length; index += 1) {
                if (instance.options[index].button === button) {
                    highlight(instance, index);
                    break;
                }
            }
        });
        button.addEventListener('click', function () {
            if (option.disabled) return;
            instance.select.value = option.value;
            sync(instance);
            emitChange(instance.select);
            close(instance);
            instance.trigger.focus();
        });
        instance.menu.appendChild(button);
        instance.options.push({ button: button, option: option });
    }

    function enhance(select) {
        if (!select || select.multiple || select.getAttribute('data-native-select') !== null
            || select.getAttribute('data-custom-select-ready') === '1') return;

        var parent = select.parentNode;
        if (!parent) return;
        var wrapper = document.createElement('div');
        wrapper.className = 'custom-select';
        wrapper.setAttribute('data-select-name', select.name || '');
        wrapper.setAttribute('data-select-class', select.className || '');
        parent.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        var label = document.createElement('span');
        label.className = 'custom-select-label';
        var chevron = document.createElement('span');
        chevron.className = 'custom-select-chevron';
        chevron.setAttribute('aria-hidden', 'true');
        trigger.appendChild(label);
        trigger.appendChild(chevron);
        wrapper.appendChild(trigger);

        var menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.hidden = true;
        menu.setAttribute('role', 'listbox');
        menu.id = 'custom-select-menu-' + Math.random().toString(36).slice(2);
        trigger.setAttribute('aria-controls', menu.id);
        wrapper.appendChild(menu);

        var instance = {
            select: select,
            wrapper: wrapper,
            trigger: trigger,
            label: label,
            menu: menu,
            options: [],
            highlighted: 0
        };
        var index;
        for (index = 0; index < select.options.length; index += 1) {
            if (!select.options[index].hidden) addOption(instance, select.options[index]);
        }

        trigger.addEventListener('click', function () {
            if (wrapper.classList.contains('is-open')) close(instance);
            else open(instance);
        });
        trigger.addEventListener('keydown', function (event) {
            var key = event.key || event.keyCode;
            if (key === 'ArrowDown' || key === 40) {
                event.preventDefault();
                if (!wrapper.classList.contains('is-open')) open(instance);
                else highlight(instance, instance.highlighted + 1);
            } else if (key === 'ArrowUp' || key === 38) {
                event.preventDefault();
                if (!wrapper.classList.contains('is-open')) open(instance);
                else highlight(instance, instance.highlighted - 1);
            } else if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
                event.preventDefault();
                if (!wrapper.classList.contains('is-open')) open(instance);
                else if (instance.options[instance.highlighted]) instance.options[instance.highlighted].button.click();
            } else if (key === 'Escape' || key === 27) {
                close(instance);
            } else if (key === 'Tab' || key === 9) {
                close(instance);
            }
        });
        select.addEventListener('change', function () { sync(instance); });
        if (select.form) {
            select.form.addEventListener('reset', function () {
                window.setTimeout(function () { sync(instance); }, 0);
            });
        }

        select.setAttribute('data-custom-select-ready', '1');
        instances.push(instance);
        sync(instance);
    }

    function initialize() {
        var selects = document.querySelectorAll('select:not([multiple]):not([data-native-select])');
        var index;
        for (index = 0; index < selects.length; index += 1) enhance(selects[index]);
    }

    document.addEventListener('click', function (event) {
        if (!closestByClass(event.target, 'custom-select')) closeAll(null);
    });
    document.addEventListener('keydown', function (event) {
        if ((event.key || event.keyCode) === 'Escape' || event.keyCode === 27) closeAll(null);
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
}());
