// Installable app: register the service worker (secure origins only) and
// offer an "Install app" button where the browser supports it.
if ('serviceWorker' in navigator && (window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    document.querySelectorAll('[data-install-app]').forEach((el) => el.removeAttribute('hidden'));
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    document.querySelectorAll('[data-install-app]').forEach((el) => el.setAttribute('hidden', ''));
});

document.addEventListener('click', (event) => {
    if (installPrompt && event.target.closest('[data-install-app]')) {
        installPrompt.prompt();
        installPrompt = null;
    }
});
