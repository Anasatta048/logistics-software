{{--
    The one shared map under the Origin / Destination fields. It shows nothing
    (and downloads no map library) until the first address is pinned, then draws
    A and B, the line between them and the driving miles.
--}}
@php
    $engine = \App\Support\MapProvider::engine();
@endphp

<div
    class="space-y-3"
    x-data="routeMap(@js([
        'engine' => $engine,
        'tilesKey' => config('services.geoapify.tiles_key'),
        'googleKey' => $engine === 'google' ? config('services.google_maps.browser_key') : null,
    ]))"
>
    {{-- Toolbar: which end a click sets, and the distance --}}
    <div class="flex flex-wrap items-center justify-between gap-3" x-show="hasPoints" x-cloak>
        <div class="flex items-center gap-2 text-xs text-text-muted">
            <span>{{ __('Click the map to set') }}</span>
            <div class="inline-flex overflow-hidden rounded-full border border-border/60 text-xs font-medium dark:border-white/10">
                <button type="button" x-on:click="setTarget('origin')"
                    x-bind:class="target === 'origin' ? 'bg-green-600 text-white' : 'text-text-muted hover:bg-border/40 dark:hover:bg-white/5'"
                    class="px-3 py-1 transition">A · {{ __('Origin') }}</button>
                <button type="button" x-on:click="setTarget('destination')"
                    x-bind:class="target === 'destination' ? 'bg-red-600 text-white' : 'text-text-muted hover:bg-border/40 dark:hover:bg-white/5'"
                    class="px-3 py-1 transition">B · {{ __('Destination') }}</button>
            </div>
            <span class="hidden sm:inline">{{ __('or drag a pin') }}</span>
        </div>

        <div class="inline-flex items-center gap-2 rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-medium text-brand-primary" x-show="miles !== null || message" x-cloak>
            <x-icon name="truck" :size="14" />
            <span x-show="miles !== null" x-text="Number(miles).toLocaleString('en-US', { maximumFractionDigits: 1 }) + ' mi'"></span>
            <span class="font-normal text-text-muted" x-text="message"></span>
        </div>
    </div>

    {{-- Empty state: no map is loaded until there is something to show --}}
    <div x-show="! hasPoints"
        class="flex h-32 flex-col items-center justify-center gap-1 rounded-card border border-dashed border-border text-center dark:border-white/15">
        <span class="text-text-muted"><x-icon name="map-pin" :size="22" /></span>
        <p class="text-sm font-medium text-text-primary dark:text-white">{{ __('Route preview') }}</p>
        <p class="text-xs text-text-muted">{{ __('Pick an origin and a destination and the route appears here.') }}</p>
    </div>

    {{-- Map --}}
    <div x-show="hasPoints" x-cloak class="relative">
        <div data-route-map class="isolate h-80 w-full overflow-hidden rounded-card border border-border/60 shadow-card dark:border-white/10"></div>

        <div x-show="booting" x-cloak class="absolute inset-0 flex items-center justify-center rounded-card bg-surface/70 backdrop-blur-sm dark:bg-surface-dark/70">
            <svg class="h-6 w-6 animate-spin text-brand-primary" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
        </div>
    </div>

    <p class="text-xs text-status-cancelled" x-show="error" x-text="error" x-cloak></p>
</div>
