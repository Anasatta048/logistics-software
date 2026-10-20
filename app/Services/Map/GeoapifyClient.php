<?php

namespace App\Services\Map;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin server-side wrapper around Geoapify (autocomplete, reverse geocode,
 * truck routing). The key never reaches the browser through this class; the
 * Load form talks to our own /maps endpoints and we call Geoapify from here.
 * US only: dispatch happens in the United States.
 */
final class GeoapifyClient implements PlaceLookup
{
    private const BASE = 'https://api.geoapify.com/v1';

    public function __construct(private readonly ?string $apiKey)
    {
    }

    public function enabled(): bool
    {
        return is_string($this->apiKey) && $this->apiKey !== '';
    }

    /**
     * @return list<array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}>
     */
    public function autocomplete(string $text, ?float $biasLat = null, ?float $biasLng = null, ?string $token = null): array
    {
        $text = trim($text);

        if (! $this->enabled() || mb_strlen($text) < 3) {
            return [];
        }

        $params = ['text' => $text, 'filter' => 'countrycode:us', 'limit' => 6, 'lang' => 'en', 'format' => 'json'];

        if ($biasLat !== null && $biasLng !== null) {
            $params['bias'] = "proximity:{$biasLng},{$biasLat}";
        }

        $key = 'geoapify:ac:'.md5(json_encode($params));

        if (is_array($hit = Cache::get($key)) && $hit !== []) {
            return $hit;
        }

        $results = collect($this->get('/geocode/autocomplete', $params)['results'] ?? [])
            ->filter(fn ($r) => isset($r['lat'], $r['lon']))
            ->map(fn (array $r): array => $this->place($r))
            ->values()
            ->all();

        // A failed or empty lookup must not stick for hours.
        if ($results !== []) {
            Cache::put($key, $results, now()->addHours(6));
        }

        return $results;
    }

    /** Geoapify suggestions already carry coordinates, so there is nothing to resolve. */
    public function details(string $placeId, ?string $token = null): ?array
    {
        return null;
    }

    /**
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}|null
     */
    public function reverse(float $lat, float $lng): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $result = $this->get('/geocode/reverse', ['lat' => $lat, 'lon' => $lng, 'lang' => 'en', 'format' => 'json'])['results'][0] ?? null;

        return is_array($result) && isset($result['lat'], $result['lon'])
            ? [...$this->place($result), 'lat' => $lat, 'lng' => $lng]
            : null;
    }

    /**
     * First match for a free-text address (US), or null.
     *
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}|null
     */
    public function search(string $text): ?array
    {
        if (! $this->enabled() || trim($text) === '') {
            return null;
        }

        $result = $this->get('/geocode/search', ['text' => $text, 'filter' => 'countrycode:us', 'limit' => 1, 'format' => 'json'])['results'][0] ?? null;

        return is_array($result) && isset($result['lat'], $result['lon']) ? $this->place($result) : null;
    }

    /**
     * Driving miles for a loaded truck, or null when Geoapify can't answer.
     */
    public function truckMiles(float $originLat, float $originLng, float $destLat, float $destLng): ?float
    {
        $miles = $this->routing($originLat, $originLng, $destLat, $destLng)['properties']['distance'] ?? null;

        return is_numeric($miles) ? round((float) $miles, 1) : null;
    }

    /**
     * The road the truck would take, thinned to a few hundred [lat, lng] points
     * (enough to draw a smooth line, small enough to send with every lookup).
     *
     * @return list<array{0: float, 1: float}>
     */
    public function routePath(float $originLat, float $originLng, float $destLat, float $destLng, int $maxPoints = 400): array
    {
        $geometry = $this->routing($originLat, $originLng, $destLat, $destLng)['geometry'] ?? null;

        if (! is_array($geometry)) {
            return [];
        }

        // LineString: [[lon, lat], …]; MultiLineString: [[[lon, lat], …], …]
        $lines = ($geometry['type'] ?? '') === 'MultiLineString' ? ($geometry['coordinates'] ?? []) : [$geometry['coordinates'] ?? []];
        $points = [];

        foreach ($lines as $line) {
            foreach ($line as $coordinate) {
                if (isset($coordinate[0], $coordinate[1])) {
                    $points[] = [round((float) $coordinate[1], 5), round((float) $coordinate[0], 5)];
                }
            }
        }

        $count = count($points);

        if ($count <= $maxPoints) {
            return $points;
        }

        $step = (int) ceil($count / $maxPoints);
        $thinned = [];

        for ($i = 0; $i < $count; $i += $step) {
            $thinned[] = $points[$i];
        }

        $thinned[] = $points[$count - 1];

        return $thinned;
    }

    /**
     * One routing call per lane, shared by the miles and the line (cached, so a
     * pin that is dragged back to a spot costs nothing).
     *
     * @return array<string, mixed>
     */
    private function routing(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $key = 'geoapify:route:'.md5(round($originLat, 4).','.round($originLng, 4).'|'.round($destLat, 4).','.round($destLng, 4));

        if (is_array($hit = Cache::get($key)) && $hit !== []) {
            return $hit;
        }

        $response = $this->get('/routing', [
            'waypoints' => "{$originLat},{$originLng}|{$destLat},{$destLng}",
            'mode' => 'truck',
            'units' => 'imperial',
        ]);

        $feature = $response['features'][0] ?? [];

        if (isset($feature['properties']['distance'])) {
            Cache::put($key, $feature, now()->addDay());
        }

        return is_array($feature) ? $feature : [];
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}
     */
    private function place(array $r): array
    {
        return [
            'placeId' => null,
            'label' => (string) ($r['formatted'] ?? ''),
            'title' => (string) ($r['address_line1'] ?? $r['formatted'] ?? ''),
            'subtitle' => (string) ($r['address_line2'] ?? ''),
            'lat' => (float) $r['lat'],
            'lng' => (float) $r['lon'],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params): array
    {
        try {
            $response = Http::timeout(10)->retry(1, 200, throw: false)->get(self::BASE.$path, [...$params, 'apiKey' => $this->apiKey]);

            if ($response->failed()) {
                Log::warning('Geoapify request failed', ['path' => $path, 'status' => $response->status()]);

                return [];
            }

            return (array) $response->json();
        } catch (ConnectionException $e) {
            Log::warning('Geoapify unreachable', ['path' => $path, 'error' => $e->getMessage()]);

            return [];
        }
    }
}
