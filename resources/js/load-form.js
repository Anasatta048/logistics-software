/**
 * Load form lookups: broker by MC number, company by name/MC, driver by
 * name/phone — all Select2 pickers that search the server as you type.
 *
 * - Broker: a known MC number fills the broker's details and locks them; an MC
 *   number that is not on file stays editable and the broker is created when
 *   the load is saved (LoadService::resolveParties).
 * - Company: picking one loads its drivers and pre-fills the default commission.
 * - Driver: searches inside the chosen company; a name that is not on file
 *   opens phone/truck fields and is created under that company on save.
 *
 * The server stays the authority: it re-validates everything and ignores the
 * "new" fields whenever an existing record was picked.
 */
import $ from 'jquery';
import select2 from 'select2';

window.$ = window.jQuery = $;
select2(window, $);

const NEW = 'new:';

/** Two-line result row; built with .text() so nothing typed is ever parsed as HTML. */
function row(title, sub, badge) {
    const $row = $('<div class="lookup-row"></div>');
    const $top = $('<div class="lookup-row-title"></div>').text(title);

    if (badge) {
        $top.append($('<span class="lookup-badge"></span>').text(badge));
    }

    $row.append($top);

    if (sub) {
        $row.append($('<div class="lookup-row-sub"></div>').text(sub));
    }

    return $row;
}

function newRow(label, term) {
    return $('<div class="lookup-row lookup-row-new"></div>')
        .append($('<span class="lookup-plus">+</span>'))
        .append($('<span></span>').text(`${label} “${term}”`));
}

/** Put the "add new" choice after the real matches, and never when the exact value already exists. */
function insertNewLast(isDuplicate) {
    return (data, tag) => {
        if (!data.some((item) => isDuplicate(item, tag.text))) {
            data.push(tag);
        }
    };
}

function setChip(el, kind, text) {
    if (!el) {
        return;
    }

    el.className = `lookup-chip ${kind ? `is-${kind}` : 'hidden'}`;
    el.textContent = text || '';
}

function setFields(fields, values, locked) {
    fields.forEach((field) => {
        const key = field.dataset.brokerField || field.dataset.driverField;

        if (values) {
            field.value = values[key] ?? '';
        }

        field.disabled = locked;
        field.classList.toggle('is-locked', locked);
    });
}

function selectedData($select, attr) {
    const raw = $select.find('option:selected').attr(attr);

    try {
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

/* ───────────────────────────── Broker ───────────────────────────── */

function initBroker(root) {
    const $select = $('#broker_lookup');
    const idInput = document.getElementById('broker_id');
    const mcInput = document.getElementById('new_broker_mc');
    const chip = document.getElementById('broker-state');
    const fields = Array.from(root.querySelectorAll('[data-broker-field]'));

    const existing = (broker) => {
        idInput.value = broker.id;
        mcInput.value = '';
        mcInput.disabled = true;
        setFields(fields, broker, true);
        setChip(chip, 'existing', 'Existing broker · details locked');
    };

    const fresh = (mc, keepValues = false) => {
        idInput.value = '';
        mcInput.value = mc;
        mcInput.disabled = false;
        setFields(fields, keepValues ? null : {}, false);
        setChip(chip, 'new', 'New broker · will be saved with this load');
        fields[0]?.focus();
    };

    const clear = () => {
        idInput.value = '';
        mcInput.value = '';
        mcInput.disabled = true;
        setFields(fields, {}, true);
        setChip(chip, null);
    };

    $select.select2({
        width: '100%',
        placeholder: $select.data('placeholder'),
        allowClear: true,
        tags: true,
        ajax: {
            url: root.dataset.brokerSearch,
            delay: 250,
            dataType: 'json',
            data: (params) => ({ q: params.term || '' }),
            processResults: (payload) => ({
                results: payload.data.map((broker) => ({
                    id: broker.id,
                    text: `${broker.broker_mc_number} — ${broker.company_name}`,
                    broker,
                    disabled: broker.status === 'inactive',
                })),
            }),
        },
        createTag: (params) => {
            const term = $.trim(params.term);

            return term.length < 3 ? null : { id: NEW + term, text: term, isNew: true };
        },
        insertTag: insertNewLast((item, term) => item.broker?.broker_mc_number?.toLowerCase() === term.toLowerCase()),
        templateResult: (item) => {
            if (item.loading) {
                return item.text;
            }

            if (item.isNew) {
                return newRow('Add new broker', item.text);
            }

            if (!item.broker) {
                return item.text;
            }

            return row(
                `${item.broker.broker_mc_number} — ${item.broker.company_name}`,
                '',
                item.broker.status === 'inactive' ? 'Inactive' : null,
            );
        },
    });

    $select.on('select2:select', (event) => {
        const data = event.params.data;

        if (data.isNew) {
            fresh(data.text);
        } else if (data.broker) {
            existing(data.broker);
        }
    });

    $select.on('select2:clear', clear);

    // Initial state: a saved broker, a typed-in one (after a validation error), or nothing.
    const saved = selectedData($select, 'data-broker');

    if (saved) {
        existing(saved);
    } else if ($select.find('option:selected').attr('data-new')) {
        fresh(mcInput.value, true);
    } else {
        clear();
    }
}

/* ───────────────────────────── Driver ───────────────────────────── */

function initDriver(root, $company, setCompany) {
    const $select = $('#driver_lookup');
    const idInput = document.getElementById('driver_id');
    const nameInput = document.getElementById('new_driver_name');
    const chip = document.getElementById('driver-state');
    const hint = document.getElementById('driver-hint');
    const fields = Array.from(root.querySelectorAll('[data-driver-field]'));

    const existing = (driver, initial = false) => {
        idInput.value = driver.id;
        nameInput.value = '';
        nameInput.disabled = true;
        setFields(fields, driver, true);
        setChip(chip, 'existing', 'Existing driver · details locked');
        hint.textContent = '';
        showOpenLoads(driver);
        if (initial) {
            lockEquipment(driver.equipment_type);
        } else {
            setCompany(driver);
            applyTruck(driver);
        }
    };

    // The driver's current (not yet delivered) loads, tucked into an accordion.
    // A busy driver can still be booked ahead, so this is information, not a block.
    const showOpenLoads = (driver) => {
        const box = document.getElementById('driver-loads');
        const title = document.getElementById('driver-loads-title');
        const list = document.getElementById('driver-loads-list');

        if (!box) {
            return;
        }

        const current = root.dataset.currentLoad;
        const loads = (driver?.open_loads || []).filter((load) => String(load.id) !== String(current));

        list.replaceChildren();
        box.open = false;
        box.classList.toggle('hidden', loads.length === 0);

        if (loads.length === 0) {
            return;
        }

        title.textContent = `${driver.name} is on ${loads.length} open load${loads.length > 1 ? 's' : ''}`;

        loads.forEach((load) => {
            const item = document.createElement('li');
            item.className = 'flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-3 py-2';

            const left = document.createElement('div');
            const ref = document.createElement('div');
            ref.className = 'font-medium text-text-primary dark:text-white';
            ref.textContent = load.reference || `Load #${load.id}`;
            const route = document.createElement('div');
            route.className = 'text-xs text-text-muted';
            route.textContent = `${load.origin} → ${load.destination} · pickup ${load.pickup}`;
            left.append(ref, route);

            const badge = document.createElement('span');
            badge.className = 'lookup-badge';
            badge.textContent = load.status_label;

            item.append(left, badge);
            list.append(item);
        });
    };

    // A saved driver's truck decides the equipment type, so the field is
    // locked to it. A disabled select is not submitted, hence the hidden twin.
    const lockEquipment = (value) => {
        const equipment = document.getElementById('equipment_type');
        const twinId = 'equipment_type_locked';
        let twin = document.getElementById(twinId);

        if (!equipment) {
            return;
        }

        if (!value) {
            twin?.remove();
            equipment.name = 'equipment_type';
            equipment.disabled = false;
            $(equipment).trigger('change.select2');

            return;
        }

        if (!twin) {
            twin = document.createElement('input');
            twin.type = 'hidden';
            twin.id = twinId;
            twin.name = 'equipment_type';
            equipment.after(twin);
        }

        twin.value = value;
        equipment.removeAttribute('name');
        equipment.disabled = true;
        $(equipment).trigger('change.select2');
    };

    // The truck is the driver's, so equipment and dimensions follow the pick.
    const applyTruck = (driver) => {
        const equipment = document.getElementById('equipment_type');
        const info = document.getElementById('driver-truck-info');

        if (equipment && driver?.equipment_type) {
            $(equipment).val(driver.equipment_type).trigger('change');
        }

        lockEquipment(driver?.equipment_type);

        if (info) {
            const dims = [driver?.truck_length_ft, driver?.truck_width_ft, driver?.truck_height_ft]
                .map((value) => (value === null || value === undefined ? '—' : Number(value)));
            const hasDims = dims.some((value) => value !== '—');
            const max = driver?.truck_max_weight_lbs;
            const parts = [];

            if (hasDims) parts.push(`Truck ${dims.join(' × ')} ft`);
            if (max) parts.push(`max ${Number(max).toLocaleString()} lbs`);

            info.textContent = parts.join(' · ');
            info.classList.toggle('hidden', parts.length === 0);
        }
    };

    const fresh = (name, keepValues = false) => {
        idInput.value = '';
        nameInput.value = name;
        nameInput.disabled = false;
        setFields(fields, keepValues ? null : {}, false);
        lockEquipment(null);
        showOpenLoads(null);
        setChip(chip, 'new', 'New driver · added to the chosen company on save');
        hint.textContent = 'Enter the phone and truck number, then choose the company below.';
        fields[0]?.focus();
    };

    const clear = () => {
        idInput.value = '';
        nameInput.value = '';
        nameInput.disabled = true;
        setFields(fields, {}, true);
        applyTruck(null);
        showOpenLoads(null);
        setChip(chip, null);
        hint.textContent = 'Search active drivers, or type a new name to add one. Leave empty to assign later.';
    };

    $select.select2({
        width: '100%',
        placeholder: $select.data('placeholder'),
        allowClear: true,
        tags: true,
        ajax: {
            url: root.dataset.driverSearch,
            delay: 250,
            dataType: 'json',
            data: (params) => ({ q: params.term || '', company_id: $company.val(), dispatcher_id: $('#dispatcher_id').val() || '' }),
            processResults: (payload) => ({
                results: payload.data.map((driver) => ({
                    id: driver.id,
                    text: `${driver.name} — ${driver.phone}`,
                    driver,
                    disabled: driver.status === 'inactive',
                })),
            }),
        },
        createTag: (params) => {
            const term = $.trim(params.term);

            return term.length < 2 ? null : { id: NEW + term, text: term, isNew: true };
        },
        insertTag: insertNewLast((item, term) => item.driver?.name?.toLowerCase() === term.toLowerCase()),
        templateResult: (item) => {
            if (item.loading) {
                return item.text;
            }

            if (item.isNew) {
                return newRow('Add new driver', item.text);
            }

            if (!item.driver) {
                return item.text;
            }

            const { driver } = item;
            const badge = driver.status === 'inactive' ? 'Inactive' : null;

            return row(driver.name, [driver.phone, driver.truck_number && `Truck ${driver.truck_number}`].filter(Boolean).join(' · '), badge);
        },
    });

    $select.on('select2:select', (event) => {
        const data = event.params.data;

        if (data.isNew) {
            fresh(data.text);
        } else if (data.driver) {
            existing(data.driver);
        }
    });

    $select.on('select2:clear', clear);

    const saved = selectedData($select, 'data-driver');

    if (saved) {
        existing(saved, true);
    } else if ($select.find('option:selected').attr('data-new')) {
        fresh(nameInput.value, true);
    } else {
        clear();
    }

    // A driver belongs to one company, so changing the company drops the pick.
    return () => {
        $select.val(null).trigger('change');
        clear();
    };
}

/* ───────────────────────────── Company ───────────────────────────── */

function initCompany(root) {
    const $select = $('#company_id');
    const percent = document.getElementById('company_commission_percentage');

    $select.select2({
        width: '100%',
        placeholder: $select.data('placeholder'),
        allowClear: true,
        ajax: {
            url: root.dataset.companySearch,
            delay: 250,
            dataType: 'json',
            data: (params) => ({ q: params.term || '', dispatcher_id: $('#dispatcher_id').val() || '' }),
            processResults: (payload) => ({
                results: payload.results.map((company) => ({
                    id: company.id,
                    text: `${company.name} (${company.mc_number})`,
                    company,
                })),
            }),
        },
        templateResult: (item) => (item.loading || !item.company
            ? item.text
            : row(item.company.name, `MC ${item.company.mc_number}`)),
    });

    let current = $select.val();
    let suppress = false;

    // Picking a driver decides the company: put it in the field without
    // triggering the "company changed, drop the driver" reset below.
    const setCompany = (driver) => {
        if (!driver.company_id || String(driver.company_id) === String($select.val())) {
            return;
        }

        suppress = true;
        $select.append(new Option(`${driver.company_name} (${driver.company_mc_number})`, driver.company_id, true, true)).trigger('change');
        suppress = false;
        current = $select.val();

        if (percent && driver.company_default_commission_percentage != null) {
            percent.value = driver.company_default_commission_percentage;
            percent.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    const resetDriver = initDriver(root, $select, setCompany);

    $select.on('select2:select', (event) => {
        const company = event.params.data.company;

        // Only the default, and only for people who can see/edit the field.
        if (percent && company?.default_commission_percentage != null) {
            percent.value = company.default_commission_percentage;
            percent.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    $select.on('change', () => {
        if (!suppress && $select.val() !== current) {
            current = $select.val();
            resetDriver();
        }
    });

    // A different dispatcher has different companies: drop the company (and with
    // it the driver) so one outside the new list cannot be left selected.
    $('#dispatcher_id').on('change', () => {
        if ($select.val()) {
            $select.val(null).trigger('change');
        }
    });
}

/* ─────────────────────── Load time zone preview ─────────────────────── */

/** Offset (ms) of a zone from UTC at a given instant. */
function zoneOffset(ms, timeZone) {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', {
            timeZone, hourCycle: 'h23', year: 'numeric', month: 'numeric', day: 'numeric',
            hour: 'numeric', minute: 'numeric', second: 'numeric',
        }).formatToParts(new Date(ms)).map((part) => [part.type, part.value]),
    );
    const asUtc = Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second);

    return asUtc - Math.floor(ms / 1000) * 1000;
}

/** The instant a wall-clock "YYYY-MM-DDTHH:mm" denotes in the given zone. */
function wallTimeToInstant(value, timeZone) {
    const [, y, m, d, h, i] = value.match(/(\d+)-(\d+)-(\d+)T(\d+):(\d+)/) || [];

    if (!y) {
        return null;
    }

    const guess = Date.UTC(y, m - 1, d, h, i);

    return guess - zoneOffset(guess - zoneOffset(guess, timeZone), timeZone);
}

function initTimezonePreview() {
    document.querySelectorAll('[data-tz-select]').forEach((input) => {
        const select = document.getElementById(input.dataset.tzSelect);
        const preview = document.querySelector(`[data-tz-preview="${input.id}"]`);
        const zones = JSON.parse(input.dataset.tzTargets || '[]');

        const render = () => {
            const source = select.value;
            const instant = input.value ? wallTimeToInstant(input.value, source) : null;

            // Show the same moment in every configured zone except the one typed in.
            const others = zones.filter((zone) => zone.timezone !== source);

            preview.textContent = instant === null || others.length === 0
                ? ''
                : `= ${others.map((zone) => `${new Intl.DateTimeFormat('en-US', {
                    timeZone: zone.timezone, month: 'short', day: 'numeric', year: 'numeric',
                    hour: 'numeric', minute: '2-digit',
                }).format(new Date(instant))} ${zone.label}`).join('  ·  ')}`;
        };

        input.addEventListener('input', render);
        input.addEventListener('change', render);
        select.addEventListener('change', render);
        render();
    });
}

function init() {
    $('[data-enhance]').select2({ width: '100%' });

    const root = document.getElementById('load-lookups');

    if (!root) {
        return;
    }

    initTimezonePreview();
    initBroker(root);
    initCompany(root);
}

document.addEventListener('DOMContentLoaded', init);
