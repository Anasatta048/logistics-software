<?php

namespace App\Services\Map;

/**
 * The Load form's geocoding and mileage needs. Two implementations exist:
 * GoogleMapService when GOOGLE_MAPS_API_KEY is set, StubMapService otherwise
 * (see App\Providers\AppServiceProvider). Both return driving-ish miles so the
 * rest of the app never branches on which one is bound.
 */
interface MapService
{
    /**
     * @return array{lat: float, lng: float, address: string}|null Null when the
     *                                                   query cannot be resolved.
     */
    public function geocode(string $query): ?array;

    /**
     * Driving miles between two points.
     */
    public function calculateDistance(float $originLat, float $originLng, float $destLat, float $destLng): float;
}
