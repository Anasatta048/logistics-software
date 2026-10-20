/**
 * Small UI behaviours shared by every screen:
 *  - customSelects(): swaps plain <select> elements for a branded dropdown
 *  - statusControl:   Alpine component that changes a load's status over AJAX
 */

import $ from 'jquery';
import select2 from 'select2';

window.$ = window.jQuery = $;
select2(window, $);

// ---- icon fields ---------------------------------------------------------
const FIELD_ICONS = {
    user: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
    phone: 'M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z',
    mail: 'M3 6h18v12H3zM3 7l9 6 9-6',
    hash: 'M5 9h14M5 15h14M10 3L8 21M16 3l-2 18',
    map: 'M9 4L3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14',
    package: 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10',
    dollar: 'M12 2v20M17 6.5C16 5 14.2 4.5 12 4.5c-2.8 0-4.5 1.2-4.5 3.2 0 4.3 9 2.3 9 6.6 0 2-1.8 3.2-4.5 3.2-2.4 0-4.3-.7-5.2-2.3',
    calendar: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
    globe: 'M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18',
    note: 'M5 3h14v18l-4-3H5zM8 8h8M8 12h6',
    briefcase: 'M3 8h18v12H3zM8 8V5a1 1 0 011-1h6a1 1 0 011 1v3M3 13h18',
    tag: 'M3 12V4h8l10 10-8 8zM7.5 8.5h.01',
    search: 'M21 21l-4.3-4.3m1.8-5.2a7 7 0 11-14 0 7 7 0 0114 0z',
};

/** Any control carrying data-icon="name" gets an icon inside it (works without markup changes). */
function decorateFields(root = document) {
    root.querySelectorAll('[data-icon]').forEach((el) => {
        if (el.dataset.iconDone || el.closest('.field-wrap')) return;
        const path = FIELD_ICONS[el.dataset.icon];
        if (!path) return;

        el.dataset.iconDone = '1';
        const wrap = document.createElement('div');
        wrap.className = 'field-wrap has-icon';
        el.before(wrap);
        wrap.insertAdjacentHTML('beforeend', `<span class="field-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="${path}"/></svg></span>`);
        wrap.appendChild(el);
    });
}



// ---- select2 on every dropdown ---------------------------------------------
// Searchable, branded and consistent. Selects owned by other code (the load
// form's lookups, DataTables' page-size box) are left alone.
const SKIP = '.select2-hidden-accessible, [data-native], [data-enhance], [data-placeholder], [data-lookup], [data-s2]';

function enhanceSelect(select) {
    if (select.matches(SKIP) || select.closest('.dt-container, template')) return;

    select.dataset.s2 = '1';

    const $select = $(select);
    const first = select.options[0];
    const hasBlank = first && first.value === '' && !select.multiple;

    $select.select2({
        width: '100%',
        minimumResultsForSearch: 0,
        closeOnSelect: !select.multiple,
        placeholder: hasBlank ? first.textContent.trim() : (select.dataset.multiPlaceholder || undefined),
        dropdownParent: select.closest('[role=dialog], .modal') ? $(select.closest('[role=dialog], .modal')) : undefined,
    });


    // Alpine's x-model and inline onchange handlers listen for native events.
    // select2 already fires a jQuery 'change' (which runs inline onchange handlers); Alpine's x-model needs a native one.
    if ([...select.attributes].some((a) => a.name.startsWith('x-'))) {
        $select.on('select2:select select2:clear', () => select.dispatchEvent(new Event('change', { bubbles: true })));
    }
}

function enhanceAll(root = document) {
    root.querySelectorAll('select').forEach(enhanceSelect);
}

/** Keep the select2 box in step when other code changes the value. */
window.refreshSelect = (select) => $(select).trigger('change.select2');

document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('main') ?? document.body;

    decorateFields(main);
    enhanceAll(main);

    // Selects rendered later (Alpine x-for rows, modals) get the same treatment.
    new MutationObserver((records) => {
        records.forEach((record) => record.addedNodes.forEach((node) => {
            if (node.nodeType !== 1) return;
            decorateFields(node);
            node.matches('select') ? enhanceSelect(node) : enhanceAll(node);
        }));
    }).observe(main, { childList: true, subtree: true });
});

// ---- status control (loads index) -----------------------------------------
function errorMessage(error) {
    const data = error.response?.data;

    return data?.message
        ?? Object.values(data?.errors ?? {})[0]?.[0]
        ?? (error.response?.status === 403 ? 'You are not allowed to do that.' : 'Something went wrong. Please try again.');
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('statusControl', (url) => ({
        open: false,
        busy: false,

        async set(status) {
            if (this.busy) return;

            this.busy = true;
            this.open = false;

            try {
                const { data } = await window.axios.post(url, { status }, { headers: { Accept: 'application/json' } });
                window.toast?.('success', data.message);

                const holder = document.createElement('div');
                holder.innerHTML = data.html.trim();
                this.$root.replaceWith(holder.firstElementChild);
            } catch (error) {
                window.toast?.('error', errorMessage(error));
                this.busy = false;
            }
        },
    }));
});

// ---- roles & rights matrix --------------------------------------------------
const ICON_PATHS = {
    truck: 'M3 7h11v8H3V7zm11 3h4l3 3v2h-7v-5zM6 18a2 2 0 100-4 2 2 0 000 4zm11 0a2 2 0 100-4 2 2 0 000 4z',
    building: 'M3 21h18M5 21V6a1 1 0 011-1h5V4a1 1 0 011-1h0a1 1 0 011 1v1h5a1 1 0 011 1v15M9 9h1m3 0h1m-5 4h1m3 0h1m-5 4h1m3 0h1',
    users: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-3-6.65',
    briefcase: 'M3 8h18v12H3zM8 8V5a1 1 0 011-1h6a1 1 0 011 1v3M3 13h18',
    package: 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10',
    'file-text': 'M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v5h5M9 13h6M9 17h6',
    'credit-card': 'M2 6h20v12H2zM2 10h20M6 15h4',
    repeat: 'M17 2l4 4-4 4M3 11V9a3 3 0 013-3h15M7 22l-4-4 4-4M21 13v2a3 3 0 01-3 3H3',
    receipt: 'M6 3h12v18l-3-2-3 2-3-2-3 2V3zm2 5h8M8 11h8M8 14h5',
    layers: 'M12 3l9 5-9 5-9-5zM3 13l9 5 9-5M3 17l9 5 9-5',
    note: 'M5 3h14v18l-4-3H5zM8 8h8M8 12h6',
    percent: 'M19 5L5 19M7 9a2 2 0 100-4 2 2 0 000 4zM17 19a2 2 0 100-4 2 2 0 000 4z',
    'chart-bar': 'M4 20V10m6 10V4m6 16v-7',
    user: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
    key: 'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.03 5.91c-.56-.1-1.16.03-1.56.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.82c0-.6.24-1.17.66-1.59l6.5-6.5c.4-.4.53-1 .43-1.56A6 6 0 0111.25 6.75V6a.75.75 0 01.75-.75h3.75z',
    shield: 'M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z',
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('roleMatrix', (modules, selected) => ({
        modules,
        picked: selected,
        q: '',
        icons: ICON_PATHS,

        get total() { return this.modules.reduce((n, m) => n + m.perms.length, 0); },
        get count() { return this.picked.length; },
        get visible() {
            const q = this.q.trim().toLowerCase();

            return q ? this.modules.filter((m) => `${m.label} ${m.desc}`.toLowerCase().includes(q)) : this.modules;
        },
        allOn(m) { return m.perms.every((p) => this.picked.includes(p.name)); },
        moduleOn(m) { return m.perms.some((p) => this.picked.includes(p.name)); },
        toggleModule(m) {
            const names = m.perms.map((p) => p.name);
            this.picked = this.allOn(m)
                ? this.picked.filter((n) => !names.includes(n))
                : [...new Set([...this.picked, ...names])];
        },
        setAll(on) { this.picked = on ? this.modules.flatMap((m) => m.perms.map((p) => p.name)) : []; },
        viewOnly() { this.picked = this.modules.flatMap((m) => m.perms.filter((p) => p.view).map((p) => p.name)); },
    }));
});
