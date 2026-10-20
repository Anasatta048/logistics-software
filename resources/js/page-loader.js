/**
 * Truck loader for slow moments: a thin progress bar right away, and the full
 * truck overlay if navigation / form submit takes longer than ~350ms.
 * Skips new tabs, downloads, hash links and anything that opts out with data-no-loader.
 */
const loader = document.getElementById('page-loader');
const bar = document.getElementById('page-progress');
let timer = null;

function start() {
    if (bar) { bar.style.opacity = '1'; bar.style.width = '70%'; }
    clearTimeout(timer);
    timer = setTimeout(() => loader?.classList.add('is-on'), 350);
    setTimeout(stop, 12000); // safety net: downloads never fire pageshow
}

function stop() {
    clearTimeout(timer);
    loader?.classList.remove('is-on');
    if (bar) { bar.style.width = '100%'; setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0'; }, 250); }
}

window.addEventListener('pageshow', stop);

document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-loader')) return;

    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search) || /\.(pdf|csv|xlsx?|zip|png|jpe?g|webp)$/i.test(url.pathname)) return;
    if (/export|csv|download|print/i.test(url.pathname + url.search) || a.getAttribute('href').startsWith('#') || a.getAttribute('href').startsWith('javascript')) return;

    start();
});

document.addEventListener('submit', (e) => {
    const form = e.target;
    // Defer so validation / Alpine handlers that cancel the submit don't leave the overlay stuck.
    setTimeout(() => {
        if (e.defaultPrevented || form.hasAttribute('data-no-loader') || form.target === '_blank') return;
        start();
    }, 0);
});
