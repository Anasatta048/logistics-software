/**
 * Route entry for the Load form (US only), in the Google-Places mould: type an
 * address, pick a suggestion, and both ends show up on ONE shared route map
 * with the driving miles filled in.
 *
 * Three Alpine components that talk through window events:
 *
 *   mapPicker  one per address field (origin / destination): autocomplete,
 *              hidden lat/lng. Emits  load:points  when its point changes.
 *   routeMap   the single shared map: A/B pins, the line between them, click or
 *              drag to fine-tune. Emits  route:pin  when the user moves a pin.
 *   loadForm   asks the backend for the driving miles once both ends are set.
 *              Emits  route:miles  for the map's distance chip.
 *
 * Suggestions, place details and reverse lookups go through our own /maps
 * endpoints, so the server-side key never reaches the browser. The map is drawn
 * by one of two engines, chosen on the server: Google (needs the
 * referrer-restricted browser key) or Leaflet (Geoapify/OSM tiles). Either one
 * is downloaded only when the first point exists, so a form that is never
 * filled in costs no map load at all.
 */

const US_CENTER = { lat: 39.5, lng: -98.35 };
const PIN = { origin: { color: '#16a34a', letter: 'A' }, destination: { color: '#dc2626', letter: 'B' } };
const NAMES = { origin: 'Origin', destination: 'Destination' };

const isDark = () => document.documentElement.classList.contains('dark');
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
const newToken = () => (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2));
const valid = (point) => point && point.lat !== null && point.lng !== null && Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lng));
const emit = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));

// The latest point of each end, so a component that starts later can catch up.
window.loadPoints ??= {};

// One cache across both pickers: the same city is often typed twice.
const suggestionCache = new Map();

const pinSvg = ({ color, letter }) => `<svg width="34" height="44" viewBox="0 0 34 44" xmlns="http://www.w3.org/2000/svg">
    <path d="M17 1C8.7 1 2 7.6 2 15.7 2 26.6 17 43 17 43s15-16.4 15-27.3C32 7.6 25.3 1 17 1z" fill="${color}" stroke="#fff" stroke-width="2"/>
    <circle cx="17" cy="16" r="8" fill="#fff"/>
    <text x="17" y="20" text-anchor="middle" font-family="system-ui,sans-serif" font-size="11" font-weight="700" fill="${color}">${letter}</text></svg>`;

/* ------------------------------------------------------------------ Leaflet */

function leafletTileUrl(tilesKey) {
    if (!tilesKey) {
        return 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    }

    const style = isDark() ? 'dark-matter' : 'osm-bright';
    const retina = window.devicePixelRatio > 1 ? '@2x' : '';

    return `https://maps.geoapify.com/v1/tile/${style}/{z}/{x}/{y}${retina}.png?apiKey=${tilesKey}`;
}

const LEAFLET_ATTRIBUTION = 'Powered by <a href="https://www.geoapify.com/" target="_blank" rel="noopener">Geoapify</a> · &copy; OpenMapTiles &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';

/* ------------------------------------------------------------------- Google */

let googleLoader = null;

function loadGoogle(key) {
    googleLoader ??= new Promise((resolve, reject) => {
        window.gm_authFailure = () => reject(new Error('Google rejected the Maps key (check billing, API access and referrer).'));
        window.__googleMapsReady = () => resolve(window.google);

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&v=weekly&callback=__googleMapsReady`;
        script.async = true;
        script.onerror = () => reject(new Error('Could not load Google Maps.'));
        document.head.append(script);
    });

    return googleLoader;
}

// Google has no built-in dark mode without a cloud-styled map id, so match the app by hand.
const GOOGLE_DARK = [
    { elementType: 'geometry', stylers: [{ color: '#1f2937' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#1f2937' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
    { featureType: 'poi', stylers: [{ visibility: 'off' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#374151' }] },
    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#4b5563' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
];

document.addEventListener('alpine:init', () => {
    /* ============================================================ mapPicker */

    Alpine.data('mapPicker', ({ name, autocompleteUrl, placeUrl, reverseUrl, lat, lng, address }) => ({
        name,
        lat: lat ?? null,
        lng: lng ?? null,

        query: address ?? '',
        picked: address ?? '',
        items: [],
        active: -1,
        listOpen: false,
        loading: false,
        status: '',
        token: newToken(),

        timer: null,
        controller: null,

        init() {
            this.$watch('lat', () => this.announce());
            this.$watch('lng', () => this.announce());

            // The shared map moved this end's pin (click or drag): take the new spot and name it.
            window.addEventListener('route:pin', (event) => {
                if (event.detail.name === this.name) {
                    this.pinAt(event.detail.lat, event.detail.lng);
                }
            });

            // An edit page already has this end: tell the map and the miles logic.
            this.announce(true);
        },

        /* ---------------------------------------------------------------- typing */

        onInput() {
            // Editing after a pick invalidates the old coordinates.
            if (this.query !== this.picked && this.lat !== null) {
                this.lat = null;
                this.lng = null;
            }

            this.status = '';
            clearTimeout(this.timer);

            const q = this.query.trim();

            if (q.length < 3) {
                this.items = [];
                this.listOpen = false;
                this.controller?.abort();
                this.loading = false;

                return;
            }

            this.timer = setTimeout(() => this.fetchSuggestions(q), 300);
        },

        async fetchSuggestions(q) {
            const key = q.toLowerCase();

            if (suggestionCache.has(key)) {
                this.show(suggestionCache.get(key));

                return;
            }

            // Only the latest keystroke matters.
            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;

            try {
                const url = new URL(autocompleteUrl, window.location.origin);
                url.searchParams.set('q', q);
                url.searchParams.set('token', this.token);

                // Bias toward the other end of the lane when it is known.
                const other = window.loadPoints[this.name === 'origin' ? 'destination' : 'origin'];
                const near = valid({ lat: this.lat, lng: this.lng }) ? { lat: this.lat, lng: this.lng } : (valid(other) ? other : null);

                if (near) {
                    url.searchParams.set('lat', near.lat);
                    url.searchParams.set('lng', near.lng);
                }

                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.controller.signal,
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const { data } = await response.json();

                if (data.length) {
                    suggestionCache.set(key, data);
                }

                this.show(data);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                this.items = [];
                this.listOpen = false;
                this.status = 'Address search is unavailable — try again, or click the map to drop a pin.';
            } finally {
                this.loading = false;
            }
        },

        show(items) {
            this.items = items;
            this.active = items.length ? 0 : -1;
            this.listOpen = true;
        },

        /* -------------------------------------------------------------- keyboard */

        focus() {
            emit('route:active', { name: this.name });

            if (this.items.length) {
                this.listOpen = true;
            }
        },

        move(step) {
            if (!this.items.length) {
                return;
            }

            this.listOpen = true;
            this.active = (this.active + step + this.items.length) % this.items.length;
        },

        confirm(event) {
            if (this.listOpen && this.items[this.active]) {
                event.preventDefault();
                this.pick(this.items[this.active]);
            }
        },

        close() {
            this.listOpen = false;
        },

        /* ------------------------------------------------------------------ pick */

        async pick(item) {
            this.listOpen = false;
            this.items = [];
            this.status = '';
            clearTimeout(this.timer);
            this.controller?.abort();
            this.loading = false;

            let place = item;

            // Google suggestions carry an id, not coordinates: one details call fills them in.
            if (place.lat == null) {
                this.status = 'Locating…';

                try {
                    const url = new URL(placeUrl, window.location.origin);
                    url.searchParams.set('id', item.placeId);
                    url.searchParams.set('token', this.token);

                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    const { data } = await response.json();

                    if (!data) {
                        throw new Error('no details');
                    }

                    place = data;
                } catch (error) {
                    this.status = 'Could not locate that address — pick another or click the map.';

                    return;
                }

                this.status = '';
            }

            // The session (typing → pick) is over; the next search starts a fresh one.
            this.token = newToken();

            this.query = place.label || item.label;
            this.picked = this.query;
            this.setPoint(place.lat, place.lng);
        },

        setPoint(lat, lng) {
            this.lat = Number(Number(lat).toFixed(6));
            this.lng = Number(Number(lng).toFixed(6));
        },

        // A pin moved by hand: take the spot, then name it.
        async pinAt(lat, lng) {
            this.setPoint(lat, lng);
            this.status = 'Finding address…';

            try {
                const url = new URL(reverseUrl, window.location.origin);
                url.searchParams.set('lat', this.lat);
                url.searchParams.set('lng', this.lng);

                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const { data } = await response.json();

                if (data?.label) {
                    this.query = data.label;
                    this.picked = data.label;
                    this.status = '';
                } else {
                    this.status = 'Pinned — no street address found here, so type one if you need it.';
                }
            } catch (error) {
                this.status = 'Pinned, but the address could not be looked up.';
            }
        },

        announce(initial = false) {
            const detail = { name: this.name, lat: this.lat, lng: this.lng, initial };

            window.loadPoints[this.name] = detail;

            if (!initial || valid(detail)) {
                emit('load:points', detail);
            }
        },
    }));

    /* ============================================================= routeMap */

    Alpine.data('routeMap', ({ engine, tilesKey, googleKey }) => {
        // Map objects stay out of Alpine's reactive proxy (a proxied Google map ignores resize() and events).
        const gfx = { map: null, tiles: null, L: null, pins: {}, line: [] };

        return {
        engine,
        points: {},
        target: 'origin', // which end a click on the map sets
        miles: null,
        path: [],
        message: '',
        error: '',
        booting: false,

        get hasPoints() {
            return valid(this.points.origin) || valid(this.points.destination);
        },

        init() {
            this.points = { ...window.loadPoints };

            window.addEventListener('load:points', (event) => {
                this.points[event.detail.name] = event.detail;
                // An end moved: the old road no longer fits until the new one arrives.
                this.path = [];
                this.pickTarget();
                this.render();
            });

            window.addEventListener('route:active', (event) => {
                this.target = event.detail.name;
            });

            window.addEventListener('route:miles', (event) => {
                this.miles = event.detail.miles;
                this.message = event.detail.message ?? '';
                this.path = event.detail.path ?? [];

                if (gfx.map) {
                    this.drawLine();
                    this.fit();
                }
            });

            // Follow the app's light/dark switch.
            new MutationObserver(() => {
                if (this.engine === 'google') {
                    gfx.map?.setOptions({ styles: isDark() ? GOOGLE_DARK : null });
                } else {
                    gfx.tiles?.setUrl(leafletTileUrl(tilesKey));
                }
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            this.pickTarget();
            this.render();
        },

        // After an end is filled, the next click naturally sets the other one.
        pickTarget() {
            if (!valid(this.points.origin)) {
                this.target = 'origin';
            } else if (!valid(this.points.destination)) {
                this.target = 'destination';
            }
        },

        /* ---------------------------------------------------------------- draw */

        async render() {
            if (!this.hasPoints) {
                this.clear();

                return;
            }

            if (!gfx.map) {
                await this.boot();
            }

            if (gfx.map) {
                this.draw();
            }
        },

        async boot() {
            if (this.booting) {
                return;
            }

            this.booting = true;
            this.error = '';

            try {
                // The container is revealed by `hasPoints`; wait for Alpine to show it.
                await this.$nextTick();
                const container = this.$root.querySelector('[data-route-map]');

                if (!container) {
                    return;
                }

                if (this.engine === 'google') {
                    const { maps } = await loadGoogle(googleKey);

                    gfx.map = new maps.Map(container, {
                        center: US_CENTER,
                        zoom: 4,
                        styles: isDark() ? GOOGLE_DARK : null,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false,
                        clickableIcons: false,
                        gestureHandling: 'cooperative',
                    });
                    gfx.map.addListener('click', (event) => this.pinTarget(event.latLng.lat(), event.latLng.lng()));
                } else {
                    const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
                    gfx.L = L;

                    gfx.map = L.map(container, { zoomControl: true, attributionControl: true }).setView([US_CENTER.lat, US_CENTER.lng], 4);
                    gfx.tiles = L.tileLayer(leafletTileUrl(tilesKey), {
                        maxZoom: 19,
                        attribution: tilesKey ? LEAFLET_ATTRIBUTION : '&copy; OpenStreetMap contributors',
                    }).addTo(gfx.map);
                    gfx.map.on('click', (event) => this.pinTarget(event.latlng.lat, event.latlng.lng));
                }
                // The container was hidden until now, and Google only paints a map once it is on screen:
                // re-measure and re-fit when its size changes or it scrolls into view.
                new ResizeObserver(() => this.remeasure()).observe(container);
                new IntersectionObserver((entries) => entries.some((entry) => entry.isIntersecting) && this.remeasure(), { threshold: 0.1 }).observe(container);
            } catch (error) {
                this.error = error.message || 'The map could not be loaded — you can still type both addresses.';
            } finally {
                this.booting = false;
            }
        },

        remeasure() {
            if (!gfx.map) {
                return;
            }

            this.engine === 'google' ? window.google.maps.event.trigger(gfx.map, 'resize') : gfx.map.invalidateSize();
            this.fit(false);
        },

        draw() {
            if (this.engine !== 'google') {
                gfx.map.invalidateSize();
            }

            for (const name of ['origin', 'destination']) {
                const point = this.points[name];

                if (valid(point)) {
                    this.placePin(name, Number(point.lat), Number(point.lng));
                } else {
                    this.removePin(name);
                }
            }

            this.drawLine();
            this.fit();
        },

        placePin(name, lat, lng) {
            if (gfx.pins[name]) {
                this.engine === 'google' ? gfx.pins[name].setPosition({ lat, lng }) : gfx.pins[name].setLatLng([lat, lng]);

                return;
            }

            if (this.engine === 'google') {
                const { maps } = window.google;

                gfx.pins[name] = new maps.Marker({
                    map: gfx.map,
                    position: { lat, lng },
                    draggable: true,
                    title: NAMES[name],
                    icon: {
                        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(pinSvg(PIN[name]))}`,
                        scaledSize: new maps.Size(34, 44),
                        anchor: new maps.Point(17, 42),
                    },
                });
                gfx.pins[name].addListener('dragend', (event) => this.pinTarget(event.latLng.lat(), event.latLng.lng(), name));

                return;
            }

            gfx.pins[name] = gfx.L.marker([lat, lng], {
                icon: gfx.L.divIcon({
                    className: 'map-pin',
                    iconSize: [34, 44],
                    iconAnchor: [17, 42],
                    html: `<span style="filter:drop-shadow(0 3px 3px rgba(0,0,0,.35))">${pinSvg(PIN[name])}</span>`,
                }),
                draggable: true,
                keyboard: false,
                title: NAMES[name],
            }).addTo(gfx.map);
            gfx.pins[name].on('dragend', () => {
                const { lat: la, lng: ln } = gfx.pins[name].getLatLng();
                this.pinTarget(la, ln, name);
            });
        },

        removePin(name) {
            if (!gfx.pins[name]) {
                return;
            }

            this.engine === 'google' ? gfx.pins[name].setMap(null) : gfx.pins[name].remove();
            delete gfx.pins[name];
        },

        removeLine() {
            for (const overlay of gfx.line) {
                this.engine === 'google' ? overlay.setMap(null) : overlay.remove();
            }

            gfx.line = [];
        },

        // The real road when we have it (a white casing under a blue line, like a
        // navigation app); until then a dashed straight line between the two pins.
        drawLine() {
            this.removeLine();

            if (!valid(this.points.origin) || !valid(this.points.destination) || !gfx.map) {
                return;
            }

            const road = this.path.length > 1 ? this.path : null;
            const ends = [
                [Number(this.points.origin.lat), Number(this.points.origin.lng)],
                [Number(this.points.destination.lat), Number(this.points.destination.lng)],
            ];
            const coords = road ?? ends;

            if (this.engine === 'google') {
                const { maps } = window.google;
                const path = coords.map(([lat, lng]) => ({ lat, lng }));

                gfx.line = road
                    ? [
                        new maps.Polyline({ map: gfx.map, path, strokeColor: '#ffffff', strokeOpacity: 0.95, strokeWeight: 8, clickable: false, zIndex: 1 }),
                        new maps.Polyline({ map: gfx.map, path, strokeColor: '#2563eb', strokeOpacity: 1, strokeWeight: 5, clickable: false, zIndex: 2 }),
                    ]
                    : [new maps.Polyline({
                        map: gfx.map,
                        path,
                        strokeOpacity: 0,
                        icons: [{ icon: { path: 'M 0,-1 0,1', strokeOpacity: 0.85, strokeColor: '#2563eb', scale: 3 }, offset: '0', repeat: '14px' }],
                        clickable: false,
                    })];

                return;
            }

            gfx.line = road
                ? [
                    gfx.L.polyline(coords, { color: '#ffffff', weight: 9, opacity: 0.95, interactive: false, lineCap: 'round', lineJoin: 'round' }).addTo(gfx.map),
                    gfx.L.polyline(coords, { color: '#2563eb', weight: 5, opacity: 1, interactive: false, lineCap: 'round', lineJoin: 'round' }).addTo(gfx.map),
                ]
                : [gfx.L.polyline(coords, { color: '#2563eb', weight: 3, opacity: 0.85, dashArray: '6 8', interactive: false }).addTo(gfx.map)];
        },

        // Frame the whole road (or just the pins until the road arrives).
        fit(animate = true) {
            const present = ['origin', 'destination'].filter((name) => valid(this.points[name]));

            if (!present.length || !gfx.map) {
                return;
            }

            const pins = present.map((name) => [Number(this.points[name].lat), Number(this.points[name].lng)]);
            const coords = this.path.length > 1 ? this.path : pins;

            if (this.engine === 'google') {
                const { maps } = window.google;

                if (coords.length === 1) {
                    gfx.map.panTo({ lat: coords[0][0], lng: coords[0][1] });
                    gfx.map.setZoom(12);

                    return;
                }

                const bounds = new maps.LatLngBounds();
                coords.forEach(([lat, lng]) => bounds.extend({ lat, lng }));
                gfx.map.fitBounds(bounds, 70);

                return;
            }

            gfx.map.invalidateSize();

            if (coords.length === 1) {
                gfx.map.flyTo(coords[0], 12, { duration: animate ? 0.8 : 0 });

                return;
            }

            gfx.map.flyToBounds(coords, { padding: [70, 70], duration: animate ? 0.8 : 0 });
        },
        clear() {
            ['origin', 'destination'].forEach((name) => this.removePin(name));
            this.removeLine();
        },

        /* ----------------------------------------------------------- interaction */

        // A click (sets the chosen end) or a drag (moves that end) on the map.
        pinTarget(lat, lng, name = this.target) {
            emit('route:pin', { name, lat, lng });
        },

        setTarget(name) {
            this.target = name;
        },
    };
    });

    /* ============================================================== loadForm */

    // Whenever both ends have coordinates, ask the backend for the route miles
    // (debounced, so dragging a pin doesn't spam it).
    Alpine.data('loadForm', ({ distanceUrl }) => ({
        distanceUrl,
        points: {},
        timer: null,
        controller: null,

        init() {
            window.addEventListener('load:points', (event) => {
                this.points[event.detail.name] = event.detail;

                // Opening an existing load must not overwrite the miles it was saved with.
                if (event.detail.initial) {
                    return;
                }

                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.refreshMiles(), 500);
            });
        },

        async refreshMiles() {
            const { origin, destination } = this.points;

            if (!valid(origin) || !valid(destination)) {
                emit('route:miles', { miles: null, message: '' });

                return;
            }

            this.controller?.abort();
            this.controller = new AbortController();
            emit('route:miles', { miles: null, message: 'Calculating driving distance…' });

            try {
                const response = await fetch(this.distanceUrl, {
                    method: 'POST',
                    signal: this.controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body: JSON.stringify({
                        origin_lat: origin.lat,
                        origin_lng: origin.lng,
                        destination_lat: destination.lat,
                        destination_lng: destination.lng,
                    }),
                });

                if (!response.ok) {
                    emit('route:miles', { miles: null, message: 'Could not calculate distance — enter the miles.' });

                    return;
                }

                const data = await response.json();

                emit('route:miles', {
                    miles: data.miles,
                    path: data.path ?? [],
                    message: data.is_estimate ? 'Offline estimate — please confirm the miles.' : 'Driving distance',
                });

                if (this.$refs.miles) {
                    this.$refs.miles.value = data.miles;
                    this.$refs.miles.dispatchEvent(new Event('input', { bubbles: true }));
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    emit('route:miles', { miles: null, message: 'Could not calculate distance — enter the miles.' });
                }
            }
        },
    }));
});
