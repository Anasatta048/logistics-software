<?php

namespace App\Services\Map;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side Google Maps Platform calls: Places (New) autocomplete + details,
 * Geocoding, and Routes. The server key lives here only; the browser gets the
 * separate, referrer-restricted Maps JavaScript key. US only.
 */
final class GoogleClient implements PlaceLookup
{
    private const PLACES = 'https://places.googleapis.com/v1';

    public function __construct(private readonly ?string $apiKey)
    {
    }

    public function enabled(): bool
    {
        return is_string($this->apiKey) && $this->apiKey !== '';
    }

    public function autocomplete(string $text, ?float $biasLat = null, ?float $biasLng = null, ?string $token = null): array
    {
        $text = trim($text);

        if (! $this->enabled() || mb_strlen($text) < 3) {
            return [];
        }

        $body = ['input' => $text, 'includedRegionCodes' => ['us'], 'languageCode' => 'en'];

        if ($token !== null) {
            $body['sessionToken'] = $token;
        }

        if ($biasLat !== null && $biasLng !== null) {
            $body['locationBias'] = ['circle' => ['center' => ['latitude' => $biasLat, 'longitude' => $biasLng], 'radius' => 50000.0]];
        }

        // Same text near the same spot gives the same answer; the session
        // token only matters for how Google bills, not for what it returns.
        $key = 'google:ac:'.md5(json_encode([$text, $biasLat === null ? null : round($biasLat, 1), $biasLng === null ? null : round($biasLng, 1)]));

        if (is_array($hit = Cache::get($key)) && $hit !== []) {
            return $hit;
        }

        $suggestions = $this->request('POST', self::PLACES.'/places:autocomplete', [], $body)['suggestions'] ?? [];

        $results = collect($suggestions)
            ->pluck('placePrediction')
            ->filter(fn ($p) => is_array($p) && isset($p['placeId']))
            ->map(fn (array $p): array => [
                'label' => (string) ($p['text']['text'] ?? ''),
                'title' => (string) ($p['structuredFormat']['mainText']['text'] ?? $p['text']['text'] ?? ''),
                'subtitle' => (string) ($p['structuredFormat']['secondaryText']['text'] ?? ''),
                'placeId' => (string) $p['placeId'],
                'lat' => null,
                'lng' => null,
            ])
            ->take(6)
            ->values()
            ->all();

        // A failed or empty lookup must not stick for hours.
        if ($results !== []) {
            Cache::put($key, $results, now()->addHours(6));
        }

        return $results;
    }

    public function details(string $placeId, ?string $token = null): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $place = $this->request(
            'GET',
            self::PLACES.'/places/'.rawurlencode($placeId),
            ['sessionToken' => $token],
            null,
            ['X-Goog-FieldMask' => 'formattedAddress,location'],
        );

        if (! isset($place['location']['latitude'], $place['location']['longitude'])) {
            return null;
        }

        return $this->place(
            (string) ($place['formattedAddress'] ?? ''),
            (float) $place['location']['latitude'],
            (float) $place['location']['longitude'],
            $placeId,
        );
    }

    public function reverse(float $lat, float $lng): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $result = $this->geocode(['latlng' => "{$lat},{$lng}", 'result_type' => 'street_address|premise|route|locality'])['results'][0]
            ?? $this->geocode(['latlng' => "{$lat},{$lng}"])['results'][0]
            ?? null;

        return is_array($result) ? $this->place((string) ($result['formatted_address'] ?? ''), $lat, $lng) : null;
    }

    /**
     * First match for a free-text US address, or null.
     *
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}|null
     */
    public function search(string $text): ?array
    {
        if (! $this->enabled() || trim($text) === '') {
            return null;
        }

        $result = $this->geocode(['address' => $text, 'components' => 'country:US'])['results'][0] ?? null;
        $location = $result['geometry']['location'] ?? null;

        return is_array($location)
            ? $this->place((string) ($result['formatted_address'] ?? $text), (float) $location['lat'], (float) $location['lng'])
            : null;
    }

    /**
     * Driving miles between two points, or null when Routes can't answer
     * (API not enabled on the key, quota, network).
     */
    public function drivingMiles(float $originLat, float $originLng, float $destLat, float $destLng): ?float
    {
        if (! $this->enabled()) {
            return null;
        }

        $response = $this->request('POST', 'https://routes.googleapis.com/directions/v2:computeRoutes', [], [
            'origin' => ['location' => ['latLng' => ['latitude' => $originLat, 'longitude' => $originLng]]],
            'destination' => ['location' => ['latLng' => ['latitude' => $destLat, 'longitude' => $destLng]]],
            'travelMode' => 'DRIVE',
            'units' => 'IMPERIAL',
        ], ['X-Goog-FieldMask' => 'routes.distanceMeters']);

        $meters = $response['routes'][0]['distanceMeters'] ?? null;

        return is_numeric($meters) ? round(((float) $meters) / 1609.344, 1) : null;
    }

    /**
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}
     */
    private function place(string $address, float $lat, float $lng, ?string $placeId = null): array
    {
        // "206 Washington St SW, Atlanta, GA 30334, USA" -> title / rest
        [$title, $subtitle] = array_pad(explode(', ', $address, 2), 2, '');

        return ['label' => $address, 'title' => $title, 'subtitle' => $subtitle, 'placeId' => $placeId, 'lat' => $lat, 'lng' => $lng];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function geocode(array $params): array
    {
        $response = $this->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', ['language' => 'en', ...$params]);

        return in_array($response['status'] ?? null, ['OK', 'ZERO_RESULTS'], true) ? $response : [];
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $json
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, array $query = [], ?array $json = null, array $headers = []): array
    {
        try {
            $http = Http::withHeaders(['X-Goog-Api-Key' => $this->apiKey, ...$headers])->timeout(8)->retry(1, 200, throw: false);
            $query = array_filter($query, fn ($v) => $v !== null);

            // Geocoding takes the key as a query parameter; the newer APIs use the header.
            if (str_contains($url, '/geocode/')) {
                $query['key'] = $this->apiKey;
            }

            $response = $method === 'POST'
                ? $http->post($url.($query ? '?'.http_build_query($query) : ''), $json)
                : $http->get($url, $query);

            if ($response->failed()) {
                Log::warning('Google Maps request failed', ['url' => strtok($url, '?'), 'status' => $response->status(), 'reason' => $response->json('error.message')]);

                return [];
            }

            return (array) $response->json();
        } catch (ConnectionException $e) {
            Log::warning('Google Maps unreachable', ['url' => strtok($url, '?'), 'error' => $e->getMessage()]);

            return [];
        }
    }
}
