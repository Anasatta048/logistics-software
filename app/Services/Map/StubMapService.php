<?php

namespace App\Services\Map;

/**
 * Used whenever GOOGLE_MAPS_API_KEY is empty (local dev, CI, tests). No network,
 * deterministic output: straight-line distance scaled by a road factor.
 *
 * ponytail: a 1.2 road factor is crude — the real number comes from the Routes
 * API as soon as a key exists. Swap this for a fixture if mileage assertions
 * ever need to be exact.
 */
final class StubMapService implements MapService
{
    private const EARTH_RADIUS_MILES = 3958.8;

    private const ROAD_FACTOR = 1.2;

    public function geocode(string $query): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        return [
            'lat' => 0.0,
            'lng' => 0.0,
            'address' => $query,
        ];
    }

    public function calculateDistance(float $originLat, float $originLng, float $destLat, float $destLng): float
    {
        $deltaLat = deg2rad($destLat - $originLat);
        $deltaLng = deg2rad($destLng - $originLng);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($originLat)) * cos(deg2rad($destLat)) * sin($deltaLng / 2) ** 2;

        $miles = self::EARTH_RADIUS_MILES * 2 * asin(min(1, sqrt($a))) * self::ROAD_FACTOR;

        return round($miles, 1);
    }
}
