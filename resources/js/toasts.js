import Swal from 'sweetalert2';

/**
 * Toasts (top-right, auto-dismiss, closable) and SweetAlert2 confirmations.
 *
 *  - window.toast(type, message, { url, duration })
 *  - Every <x-alert> rendered inside <main> (flash messages, errors) is lifted into a toast.
 *  - Any button/form control with data-confirm="…" asks first. Delete/remove/void wording turns the button red.
 */
const ICONS = {
    success: '<path d="M20 6 9 17l-5-5"/>',
    error: '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
    warning: '<path d="M12 3 2 20h20zM12 10v4M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
};

let host = null;

function container() {
    if (!host) {
        host = document.createElement('div');
        host.id = 'toast-host';
        document.body.appendChild(host);
    }

    return host;
}

window.toast = (type = 'info', message = '', { url = null, duration = 5000 } = {}) => {
    const kind = ICONS[type] ? type : 'info';
    const el = document.createElement(url ? 'a' : 'div');
    if (url) el.href = url;
    el.className = `toast toast-${kind}`;
    el.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    el.innerHTML = `
        <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[kind]}</svg>
        <span class="toast-msg"></span>
        <button type="button" class="toast-x" aria-label="Close">&times;</button>
        <span class="toast-bar" style="animation-duration:${duration}ms"></span>`;
    el.querySelector('.toast-msg').textContent = message;

    let timer;
    const close = () => {
        clearTimeout(timer);
        el.classList.add('is-leaving');
        setTimeout(() => el.remove(), 220);
    };
    const arm = () => {
        timer = setTimeout(close, duration);
        el.classList.remove('is-paused');
    };

    el.querySelector('.toast-x').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        close();
    });
    el.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        el.classList.add('is-paused');
    });
    el.addEventListener('mouseleave', arm);

    container().appendChild(el);
    while (host.children.length > 5) host.firstElementChild.remove();
    arm();
};

function liftAlerts() {
    document.querySelectorAll('main [data-alert]').forEach((a) => {
        window.toast(a.dataset.alert, a.textContent.trim().replace(/\s+/g, ' '));
        a.remove();
    });
}

document.addEventListener('DOMContentLoaded', liftAlerts);

const DANGER = /\b(remove|delete|void|cancel|discard|revoke)\b/i;

function ask(text) {
    const danger = DANGER.test(text);

    return Swal.fire({
        title: danger ? 'Are you sure?' : 'Please confirm',
        text,
        icon: danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: danger ? 'Yes, continue' : 'Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: danger,
        buttonsStyling: false,
        customClass: {
            popup: 'swal-sq',
            title: 'swal-sq-title',
            htmlContainer: 'swal-sq-text',
            confirmButton: danger ? 'swal-btn swal-btn-danger' : 'swal-btn swal-btn-primary',
            cancelButton: 'swal-btn swal-btn-ghost',
        },
    }).then((r) => r.isConfirmed);
}

window.confirmAction = ask;

document.addEventListener('click', async (e) => {
    const el = e.target.closest('[data-confirm]');
    if (!el || el.dataset.confirmed === '1') return;

    e.preventDefault();
    e.stopImmediatePropagation();

    if (!(await ask(el.dataset.confirm))) return;

    el.dataset.confirmed = '1';
    if (el.form && (el.tagName === 'BUTTON' || el.tagName === 'INPUT')) {
        el.form.requestSubmit(el);
    } else {
        el.click();
    }
    setTimeout(() => delete el.dataset.confirmed, 0);
}, true);
