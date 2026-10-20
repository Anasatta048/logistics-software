<?php

namespace App\Services\Map;

/**
 * What the Load form's address picker needs from a places provider. Items share
 * one shape so the browser never branches on who answered:
 *
 *   label    full one-line address (goes into the input)
 *   title    first line shown in the dropdown
 *   subtitle second line shown in the dropdown
 *   placeId  provider id, when coordinates need a second call (Google)
 *   lat/lng  coordinates, or null until details() resolves the placeId
 *
 * @phpstan-type Place array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}
 */
interface PlaceLookup
{
    public function enabled(): bool;

    /**
     * @return list<array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}>
     */
    public function autocomplete(string $text, ?float $biasLat = null, ?float $biasLng = null, ?string $token = null): array;

    /**
     * Resolve a suggestion that came back without coordinates.
     *
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}|null
     */
    public function details(string $placeId, ?string $token = null): ?array;

    /**
     * @return array{label: string, title: string, subtitle: string, placeId: ?string, lat: ?float, lng: ?float}|null
     */
    public function reverse(float $lat, float $lng): ?array;
}
