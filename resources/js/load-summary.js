/**
 * Live read-only preview for the Load form's Summary panel (POS-style split
 * screen — UI_UX_DESIGN_PLAN Section 4). Purely cosmetic: it mirrors the
 * arithmetic LoadService already computes server-side (rate / miles, and
 * rate × company commission % for what we earn) so a dispatcher sees a live
 * estimate while typing. The server remains authoritative on submit.
 *
 * The commission rows exist only for users who may see company commission (accounting rights); without
 * them the percentage box is absent and those outputs are simply skipped.
 * Activated on any form carrying [data-load-summary].
 */
function formatCurrency(value) {
    if (!Number.isFinite(value)) {
        return '—';
    }

    return `$${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function initLoadSummary(form) {
    const rateInput = form.querySelector('#rate');
    const milesInput = form.querySelector('#total_miles');
    const percentInput = form.querySelector('#company_commission_percentage');

    if (!rateInput || !milesInput) {
        return;
    }

    const ratePerMileOut = document.querySelector('[data-summary-field="rate-per-mile"]');
    const marginOut = document.querySelector('[data-summary-field="margin"]');
    const marginPerMileOut = document.querySelector('[data-summary-field="margin-per-mile"]');
    const rateOut = document.querySelector('[data-summary-field="rate"]');

    const recalc = () => {
        const rate = parseFloat(rateInput.value);
        const miles = parseFloat(milesInput.value);
        const percent = percentInput ? parseFloat(percentInput.value) : NaN;

        if (rateOut) {
            rateOut.textContent = Number.isFinite(rate) ? formatCurrency(rate) : '—';
        }

        if (ratePerMileOut) {
            ratePerMileOut.textContent = Number.isFinite(rate) && miles > 0
                ? `${formatCurrency(rate / miles)} / mi`
                : '—';
        }

        const commission = Number.isFinite(rate) && Number.isFinite(percent) ? rate * percent / 100 : NaN;

        if (marginOut) {
            marginOut.textContent = formatCurrency(commission);
        }

        if (marginPerMileOut) {
            marginPerMileOut.textContent = Number.isFinite(commission) && miles > 0
                ? `${formatCurrency(commission / miles)} / mi`
                : '—';
        }
    };

    ['input', 'change'].forEach((evt) => {
        rateInput.addEventListener(evt, recalc);
        milesInput.addEventListener(evt, recalc);
        percentInput?.addEventListener(evt, recalc);
    });

    recalc();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-load-summary]').forEach(initLoadSummary);
});
