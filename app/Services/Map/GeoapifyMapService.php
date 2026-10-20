<?php

namespace App\Services\Map;

/**
 * MapService backed by Geoapify. A straight-line estimate covers the case where
 * the routing call fails, so booking a load never blocks on the network.
 */
final readonly class GeoapifyMapService implements MapService
{
    public function __construct(
        private GeoapifyClient $client,
        private MapService $fallback = new StubMapService(),
    ) {
    }

    public function geocode(string $query): ?array
    {
        $place = $this->client->search($query);

        return $place === null ? $this->fallback->geocode($query) : ['lat' => $place['lat'], 'lng' => $place['lng'], 'address' => $place['label']];
    }

    public function calculateDistance(float $originLat, float $originLng, float $destLat, float $destLng): float
    {
        return $this->client->truckMiles($originLat, $originLng, $destLat, $destLng)
            ?? $this->fallback->calculateDistance($originLat, $originLng, $destLat, $destLng);
    }
}
