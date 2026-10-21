/*
 * Service worker: makes the app installable and gives it a friendly offline
 * page. It deliberately caches only static build assets and the offline page.
 * Signed-in HTML and JSON are never stored, so one person's data can never be
 * served to another on a shared phone and nothing goes stale.
 */
const VERSION = 'v1';
const STATIC = `static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC).then((cache) => cache.addAll([OFFLINE_URL, '/favicon-192.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== STATIC).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Built, fingerprinted assets: serve from cache, fill it on first use.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((hit) => hit || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(STATIC).then((cache) => cache.put(request, copy));
                }

                return response;
            })),
        );

        return;
    }

    // Pages: always the network; offline shows the fallback instead of the browser's error.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    }
});
