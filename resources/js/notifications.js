/**
 * Live notification bell: unread badge, in-app toast, a short chime, and a
 * desktop (browser) notification when the tab is in the background.
 *
 * Delivery is a private Reverb channel per user; Laravel pushes the same
 * payload it stores in the `notifications` table.
 */

let audio = null;

// Browsers only let audio start after a user gesture, so unlock on first input.
function unlockAudio() {
    try {
        audio ??= new (window.AudioContext || window.webkitAudioContext)();
        if (audio.state === 'suspended') audio.resume();
    } catch (e) { /* no audio support */ }
}
['click', 'keydown', 'touchstart'].forEach((evt) => window.addEventListener(evt, unlockAudio, { once: true, passive: true }));

function chime() {
    if (!audio || audio.state !== 'running') return;

    [[880, 0], [1320, 0.14]].forEach(([freq, delay]) => {
        const osc = audio.createOscillator();
        const gain = audio.createGain();
        const t = audio.currentTime + delay;

        osc.type = 'sine';
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.0001, t);
        gain.gain.exponentialRampToValueAtTime(0.25, t + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
        osc.connect(gain).connect(audio.destination);
        osc.start(t);
        osc.stop(t + 0.4);
    });
}

function desktopNotify(title, body, url, tag) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;

    try {
        const n = new Notification(title, { body, icon: '/images/logo.png', tag });
        n.onclick = () => {
            window.focus();
            if (url) window.location.href = url;
            n.close();
        };
    } catch (e) { /* some browsers only allow it via a service worker */ }
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('notificationBell', (cfg) => ({
        unread: cfg.unread,
        open: false,
        items: cfg.items ?? [],
        toasts: [],
        permission: 'Notification' in window ? Notification.permission : 'unsupported',

        init() {
            this.catchUp();

            if (!window.Echo) return;

            window.Echo.private(`App.Models.User.${cfg.userId}`).notification((n) => this.receive(n));
        },

        receive(n) {
            this.unread++;
            this.items.unshift({ id: n.id, message: n.message, url: n.url, read: false, time: 'just now' });
            this.items = this.items.slice(0, 8);
            chime();

            if (document.hidden) {
                desktopNotify(cfg.appName, n.message, n.url, n.id);
            }

            window.toast?.('info', n.message, { url: n.url });
        },

        post(url) {
            return fetch(url, {
                method: 'POST',
                keepalive: true,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            }).catch(() => {});
        },

        async openItem(item) {
            if (!item.read) {
                item.read = true;
                this.unread = Math.max(0, this.unread - 1);
                await this.post(cfg.readUrl.replace('__ID__', item.id));
            }

            if (item.url) window.location.href = item.url;
        },

        async markAll() {
            this.items.forEach((i) => (i.read = true));
            this.unread = 0;
            await this.post(cfg.readAllUrl);
        },

        dismiss(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },

        // Once per browser session (i.e. when the user opens the app / signs in),
        // surface what they missed while away.
        catchUp() {
            if (this.permission !== 'granted' || this.unread < 1) return;

            try {
                if (sessionStorage.getItem('notif-welcome') === cfg.userId.toString()) return;
                sessionStorage.setItem('notif-welcome', cfg.userId.toString());
            } catch (e) { return; }

            const latest = cfg.latest[0];
            desktopNotify(
                cfg.appName,
                this.unread === 1 ? latest?.message : `You have ${this.unread} unread notifications.`,
                this.unread === 1 ? latest?.url : cfg.indexUrl,
                'welcome',
            );
        },

        async enable() {
            unlockAudio();
            this.permission = await Notification.requestPermission();

            if (this.permission === 'granted') {
                chime();
                desktopNotify(cfg.appName, 'Desktop alerts are on.', null, 'enabled');
            }
        },
    }));
});
