<?php

namespace App\Support;

/**
 * Which maps vendor answers, decided in one place from config:
 *
 *   MAP_PROVIDER=google|geoapify   explicit choice (falls through if its key is missing)
 *   otherwise                      Geoapify if keyed, else Google, else none
 *
 * "engine" is the map the browser draws: Google's JS map needs the separate,
 * referrer-restricted browser key; without it the Leaflet map is used (still
 * fed by whichever provider is searching).
 */
final class MapProvider
{
    public static function name(): string
    {
        $google = filled(config('services.google_maps.key'));
        $geoapify = filled(config('services.geoapify.key'));

        return match (config('services.map_provider')) {
            'google' => $google ? 'google' : ($geoapify ? 'geoapify' : 'none'),
            'geoapify' => $geoapify ? 'geoapify' : ($google ? 'google' : 'none'),
            default => $geoapify ? 'geoapify' : ($google ? 'google' : 'none'),
        };
    }

    public static function engine(): string
    {
        return self::name() === 'google' && filled(config('services.google_maps.browser_key')) ? 'google' : 'leaflet';
    }
}
