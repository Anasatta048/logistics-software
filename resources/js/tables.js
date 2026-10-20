import DataTable from 'datatables.net';
import $ from 'jquery';

/**
 * Every <table data-table> inside <main> becomes a DataTable: one search box,
 * click-to-sort headers, page size, paging — plus instant filters built from
 * the header cells:
 *
 *   <th data-filter="select">Company</th>   a searchable dropdown of the column's values
 *   <th data-filter="date">Pickup</th>      From / To date pickers (cells carry data-date="YYYY-MM-DD")
 *
 * A cell may carry data-filter-value to override the text used for its option.
 * Filters apply the moment they change; there is no Filter button.
 */
const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

/** [from, to] for a quick-range choice; null for "custom" (leave the boxes as typed). */
function presetRange(value) {
    const now = new Date();
    const day = (offset) => { const d = new Date(now); d.setDate(d.getDate() + offset); return d; };

    switch (value) {
        case '': return ['', ''];
        case 'today': return [iso(now), iso(now)];
        case 'yesterday': return [iso(day(-1)), iso(day(-1))];
        case 'week': return [iso(day(-((now.getDay() + 6) % 7))), iso(now)];
        case '7': return [iso(day(-6)), iso(now)];
        case '30': return [iso(day(-29)), iso(now)];
        case 'month': return [iso(new Date(now.getFullYear(), now.getMonth(), 1)), iso(now)];
        case 'lastmonth': return [iso(new Date(now.getFullYear(), now.getMonth() - 1, 1)), iso(new Date(now.getFullYear(), now.getMonth(), 0))];
        case 'year': return [iso(new Date(now.getFullYear(), 0, 1)), iso(now)];
        default: return null;
    }
}

const clean = (text) => text.replace(/\s+/g, ' ').trim();

function cellValue(cell) {
    if (cell.dataset.filterValue !== undefined) return cell.dataset.filterValue.trim();

    const firstLine = cell.innerText.split('\n').map((line) => line.trim()).find(Boolean);

    return firstLine ?? '';
}

function buildFilters(table, dt, head) {
    const filters = [];

    [...head.cells].forEach((th, index) => {
        const type = th.dataset.filter;
        if (!type) return;

        const label = clean(th.textContent);
        const field = document.createElement('div');
        field.className = 'dt-filter';
        field.innerHTML = `<label>${label}</label>`;

        if (type === 'select') {
            const values = [...new Set([...table.tBodies[0].rows].map((row) => cellValue(row.cells[index])).filter(Boolean))]
                .sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
            const select = document.createElement('select');
            select.dataset.s2 = '1';
            select.innerHTML = `<option value="">All</option>${values.map((v) => `<option>${v.replace(/&/g, '&amp;').replace(/</g, '&lt;')}</option>`).join('')}`;
            field.appendChild(select);
            filters.push({ index, type, get: () => select.value, el: select });

            queueMicrotask(() => {
                $(select).select2({ width: '100%', minimumResultsForSearch: 0, placeholder: 'All', allowClear: false });
                $(select).on('change', () => dt.draw());
            });
        } else if (type === 'date') {
            const wrap = document.createElement('div');
            wrap.className = 'dt-filter-dates';
            wrap.innerHTML = `<select class="dt-preset" data-s2="1" aria-label="Quick range">
                    <option value="">Any time</option><option value="today">Today</option><option value="yesterday">Yesterday</option>
                    <option value="week">This week</option><option value="7">Last 7 days</option><option value="month">This month</option>
                    <option value="lastmonth">Last month</option><option value="30">Last 30 days</option><option value="year">This year</option>
                    <option value="custom">Custom range…</option></select>
                <input type="date" aria-label="From"><span>–</span><input type="date" aria-label="To">`;
            field.appendChild(wrap);
            const [from, to] = wrap.querySelectorAll('input');
            const preset = wrap.querySelector('select');
            [from, to].forEach((input) => input.addEventListener('input', () => { $(preset).val('custom').trigger('change.select2'); dt.draw(); }));
            queueMicrotask(() => {
                $(preset).select2({ width: '10.5rem', minimumResultsForSearch: Infinity });
                $(preset).on('change', () => {
                    const range = presetRange(preset.value);
                    if (range) { [from.value, to.value] = range; dt.draw(); }
                });
            });
            filters.push({ index, type, get: () => [from.value, to.value], el: wrap });
        }

        filters.at(-1) && (filters.at(-1).field = field);
    });

    return filters;
}

function enhance(table) {
    const body = table.tBodies[0];
    const head = table.tHead?.rows[0];

    if (!body || !head || !body.rows.length) return;
    if ([...body.rows].some((row) => [...row.cells].some((cell) => cell.colSpan > 1))) return;

    const columnDefs = [...head.cells].map((th, targets) => {
        const plain = /^(actions?)?$/i.test(th.textContent.trim());

        return { targets, orderable: !plain, searchable: !plain };
    });

    let filters = [];

    DataTable.ext.search.push((settings, _data, dataIndex) => {
        if ((settings.table ?? settings.nTable) !== table || !filters.length) return true;

        const entry = (settings.data ?? settings.aoData)[dataIndex];
        const row = entry.tr ?? entry.nTr;

        return filters.every(({ index, type, get }) => {
            const cell = row.cells[index];

            if (type === 'select') {
                const wanted = get();

                return !wanted || cellValue(cell) === wanted;
            }

            const [from, to] = get();
            const date = cell.dataset.date ?? '';

            if (!from && !to) return true;
            if (!date) return false;

            return (!from || date >= from) && (!to || date <= to);
        });
    });

    const dt = new DataTable(table, {
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        order: [],
        columnDefs,
        autoWidth: false,
        // One search box, in the filter bar above the table; size + paging below it.
        layout: { topStart: null, topEnd: null, bottomStart: ['pageLength', 'info'], bottomEnd: 'paging' },
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'Nothing to show',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: 'No matching records — try clearing a filter.',
            paginate: { previous: '‹', next: '›', first: '«', last: '»' },
        },
    });

    filters = buildFilters(table, dt, head);

    const container = table.closest('.dt-container');
    const bar = document.createElement('div');
    bar.className = 'dt-filters';
    bar.innerHTML = '<div class="dt-filter dt-filter-search"><label>Search</label><div class="dt-search-box"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 21l-4.3-4.3m1.8-5.2a7 7 0 11-14 0 7 7 0 0114 0z"/></svg><input type="search" placeholder="Type to search…"></div></div>';
    const search = bar.querySelector('input');
    search.addEventListener('input', () => dt.search(search.value).draw());
    filters.forEach(({ field }) => bar.appendChild(field));

    const reset = document.createElement('button');
    reset.type = 'button';
    reset.className = 'dt-filters-reset';
    reset.textContent = 'Clear filters';
    reset.addEventListener('click', () => {
        filters.forEach(({ type, el }) => {
            if (type === 'select') $(el).val('').trigger('change.select2');
            else { el.querySelectorAll('input').forEach((input) => (input.value = '')); $(el.querySelector('select')).val('').trigger('change.select2'); }
        });
        search.value = '';
        dt.search('').draw();
    });
    bar.appendChild(reset);

    container.insertBefore(bar, container.firstChild);
    dt.draw();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('main table[data-table]').forEach(enhance);
});
