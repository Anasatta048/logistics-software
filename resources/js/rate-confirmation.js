/**
 * "Auto-fill from rate confirmation" on the new-load form. The PDF is sent to
 * the server only to be read (nothing is saved); the answer fills the form
 * fields that are still empty and the file is added to the attachments as a
 * Rate Confirmation. Everything stays editable: these are suggestions.
 */
import $ from 'jquery';

const FIELDS = {
    reference_number: 'Load number',
    broker_contact_person: 'Contact person',
    broker_phone: 'Phone',
    broker_email: 'Email',
    equipment_type: 'Equipment',
    pickup_at: 'Pickup time',
    dropoff_at: 'Delivery time',
    total_miles: 'Miles',
    weight_lbs: 'Weight',
    rate: 'Rate',
    origin_address: 'Pickup address',
    destination_address: 'Delivery address',
};

function setValue(input, value) {
    input.value = value;
    input.dataset.autofilled = '1';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

/** Empty, or filled earlier by this tool (so a second PDF may replace it). */
const writable = (input) => input && (input.value === '' || input.dataset.autofilled === '1');

function fill(form, fields) {
    const filled = [];

    Object.entries(fields).forEach(([name, value]) => {
        const input = form.querySelector(`[name="${name}"]`);

        if (!writable(input)) return;

        if (input.tagName === 'SELECT') {
            const option = [...input.options].find((o) => o.value.toLowerCase() === value.toLowerCase());
            if (!option) return;
            value = option.value;
        }

        setValue(input, value);
        filled.push(FIELDS[name] ?? name);
    });

    return filled;
}

/** Add the PDF to the attachments list (first type is "Rate confirmation"). */
function attach(form, file) {
    const input = form.querySelector('input[name="attachments[]"]');
    const holder = input?.closest('[x-data]');

    if (!holder || !window.Alpine) return;

    const data = window.Alpine.$data(holder);

    if (data.items.some((item) => item.file.name === file.name && item.file.size === file.size)) return;

    data.add([file]);
}

function openBrokerSearch(mc) {
    const $lookup = $('#broker_lookup');

    if (!$lookup.length || $lookup.val()) return;

    $lookup.select2('open');
    setTimeout(() => {
        const search = document.querySelector('.select2-container--open .select2-search__field');

        if (search) {
            search.value = mc;
            search.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, 60);
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-ratecon]');

    if (!root) return;

    const form = root.closest('form');
    const input = root.querySelector('input[type="file"]');
    const status = root.querySelector('[data-ratecon-status]');
    const show = (text, tone = 'muted') => {
        status.textContent = text;
        status.className = `mt-2 text-sm ${tone === 'bad' ? 'text-status-cancelled' : tone === 'good' ? 'text-status-paid' : 'text-text-muted'}`;
    };

    input.addEventListener('change', async () => {
        const file = input.files[0];

        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            show('That file is larger than 5 MB.', 'bad');
            input.value = '';

            return;
        }

        show('Reading the document…');

        const body = new FormData();
        body.append('file', file);

        try {
            const response = await fetch(root.dataset.url, {
                method: 'POST',
                body,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
            });

            if (!response.ok) {
                show(response.status === 422 ? 'Please choose a PDF under 5 MB.' : 'Could not read that document. Fill the form by hand.', 'bad');

                return;
            }

            const result = await response.json();

            attach(form, file);

            if (!result.text_found) {
                show('This PDF has no readable text (it looks like a scan), so nothing could be filled. It was attached as the rate confirmation.', 'bad');

                return;
            }

            const filled = fill(form, result.fields);
            const notes = [];

            if (result.broker?.mc) {
                notes.push(`broker MC ${result.broker.mc}${result.known_broker ? ' is on file' : ' (not on file yet)'}: pick it in the Broker box`);
                openBrokerSearch(result.broker.mc);
            }

            show(
                filled.length
                    ? `Filled: ${filled.join(', ')}. Please check everything${notes.length ? '; ' + notes.join('; ') : ''}.`
                    : 'No details could be recognised in this document. It was attached; fill the form by hand.',
                filled.length ? 'good' : 'muted',
            );
        } catch {
            show('Could not reach the server. Fill the form by hand.', 'bad');
        } finally {
            input.value = '';
        }
    });
});
