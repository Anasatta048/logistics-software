<?php

namespace App\Services\Map;

/**
 * MapService backed by Google (Geocoding + Routes). When Google can't answer,
 * for instance because Routes isn't enabled on the key, the next provider in
 * the chain does (Geoapify, then the offline estimate), so booking a load never
 * blocks on a map outage.
 */
final readonly class GoogleMapService implements MapService
{
    public function __construct(
        private GoogleClient $client,
        private MapService $fallback = new StubMapService(),
    ) {
    }

    public function geocode(string $query): ?array
    {
        $place = $this->client->search($query);

        return $place === null
            ? $this->fallback->geocode($query)
            : ['lat' => $place['lat'], 'lng' => $place['lng'], 'address' => $place['label']];
    }

    public function calculateDistance(float $originLat, float $originLng, float $destLat, float $destLng): float
    {
        return $this->client->drivingMiles($originLat, $originLng, $destLat, $destLng)
            ?? $this->fallback->calculateDistance($originLat, $originLng, $destLat, $destLng);
    }
}
