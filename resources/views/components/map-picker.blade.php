@props(['name', 'label', 'lat' => null, 'lng' => null, 'address' => null])

{{--
    One end of the route (origin or destination): an address box with live
    suggestions. The pin itself lives on the shared <x-route-map>; this field
    only reports its coordinates to it (see resources/js/map-picker.js).
--}}
@php
    $provider = \App\Support\MapProvider::name();
    $oldLat = old($name . '_lat', $lat);
    $oldLng = old($name . '_lng', $lng);
    $isOrigin = $name === 'origin';
@endphp

<div
    class="space-y-1.5"
    x-data="mapPicker(@js([
        'name' => $name,
        'autocompleteUrl' => route('maps.autocomplete', [], false),
        'placeUrl' => route('maps.place', [], false),
        'reverseUrl' => route('maps.reverse', [], false),
        'lat' => is_numeric($oldLat) ? (float) $oldLat : null,
        'lng' => is_numeric($oldLng) ? (float) $oldLng : null,
        'address' => old($name . '_address', $address),
    ]))"
    x-on:click.outside="close()"
>
    <x-input-label :for="$name . '_address'" :value="$label" />

    <div class="relative">
        {{-- A / B badge, matching the pin on the map --}}
        <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center">
            <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold text-white {{ $isOrigin ? 'bg-green-600' : 'bg-red-600' }}">
                {{ $isOrigin ? 'A' : 'B' }}
            </span>
        </span>

        <input
            id="{{ $name }}_address"
            name="{{ $name }}_address"
            type="text"
            required
            autocomplete="off"
            spellcheck="false"
            role="combobox"
            aria-autocomplete="list"
            x-bind:aria-expanded="listOpen"
            placeholder="{{ __('Start typing a US address, city or ZIP…') }}"
            x-model="query"
            x-on:input="onInput()"
            x-on:focus="focus()"
            x-on:keydown.arrow-down.prevent="move(1)"
            x-on:keydown.arrow-up.prevent="move(-1)"
            x-on:keydown.enter="confirm($event)"
            x-on:keydown.escape="close()"
            class="block w-full rounded-input border-border bg-surface pl-11 pr-9 text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white"
        />

        {{-- Spinner / "pinned" tick --}}
        <span class="absolute inset-y-0 right-3 flex items-center">
            <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin text-brand-primary" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            <span x-show="! loading && lat !== null" x-cloak class="text-status-paid" title="{{ __('Pinned on the map') }}">
                <x-icon name="check-circle" :size="16" />
            </span>
        </span>

        {{-- Suggestions --}}
        <ul
            x-show="listOpen" x-cloak x-transition.opacity.duration.100ms
            role="listbox"
            class="absolute z-30 mt-1 max-h-72 w-full overflow-auto rounded-card border border-border/60 bg-surface py-1 shadow-card dark:border-white/10 dark:bg-surface-dark"
        >
            <template x-for="(item, index) in items" x-bind:key="(item.placeId ?? item.lat + ',' + item.lng) + index">
                <li
                    role="option"
                    x-bind:aria-selected="index === active"
                    x-on:mousedown.prevent="pick(item)"
                    x-on:mouseenter="active = index"
                    x-bind:class="index === active ? 'bg-brand-primary/10' : ''"
                    class="flex cursor-pointer items-start gap-3 px-3 py-2"
                >
                    <span class="mt-0.5 text-text-muted"><x-icon name="map-pin" :size="16" /></span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-text-primary dark:text-white" x-text="item.title"></span>
                        <span class="block truncate text-xs text-text-muted" x-text="item.subtitle"></span>
                    </span>
                </li>
            </template>

            <li x-show="! items.length && ! loading" class="px-3 py-3 text-sm text-text-muted">
                {{ __('No US address found. Try the street, city and state, or click the map.') }}
            </li>

            <li class="border-t border-border/60 px-3 py-1.5 text-right text-[10px] text-text-muted dark:border-white/10">
                {{ $provider === 'google' ? __('Powered by Google') : __('Powered by Geoapify') }}
            </li>
        </ul>
    </div>

    {{-- Helper line --}}
    <p class="min-h-[1rem] text-xs text-text-muted">
        <span x-show="status" x-text="status" x-cloak></span>
        <span x-show="! status && lat === null && query.trim() !== ''" x-cloak>
            {{ __('Pick a suggestion to pin it and calculate miles.') }}
        </span>
    </p>

    {{-- Hidden coordinates --}}
    <input type="hidden" id="{{ $name }}_lat" name="{{ $name }}_lat" x-model="lat" />
    <input type="hidden" id="{{ $name }}_lng" name="{{ $name }}_lng" x-model="lng" />

    <x-input-error :messages="$errors->get($name . '_lat')" />
    <x-input-error :messages="$errors->get($name . '_lng')" />
</div>
