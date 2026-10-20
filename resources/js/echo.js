import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Echo only boots on pages that carry the <meta name="reverb"> tag (the
 * authenticated layout). Host/port/scheme come from the server at request time,
 * not from the Vite build, so the same image works on any domain: blank means
 * "same host as the page", which nginx proxies to the Reverb process.
 */
const meta = document.querySelector('meta[name="reverb"]');

if (meta && JSON.parse(meta.content).key) {
    const cfg = JSON.parse(meta.content);
    const secure = (cfg.scheme ?? window.location.protocol.replace(':', '')) === 'https';
    const port = Number(cfg.port || window.location.port || (secure ? 443 : 80));

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: cfg.key,
            wsHost: cfg.host || window.location.hostname,
            wsPort: port,
            wssPort: port,
            forceTLS: secure,
            enabledTransports: ['ws', 'wss'],
        });
    } catch (e) {
        // Realtime is optional: a misconfigured socket must never break the page.
        console.warn('Realtime notifications are off:', e.message);
    }
}
