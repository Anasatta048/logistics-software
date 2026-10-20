<?php

namespace App\Http\Controllers\Map;

use App\Http\Controllers\Controller;
use App\Services\Map\PlaceLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Address lookups for the Load form's pickers. Thin on purpose: the browser
 * never sees the server-side key, and the throttle in the route file keeps a
 * runaway form from burning the daily quota. Which vendor answers is decided
 * by App\Support\MapProvider; the response shape is the same for both.
 */
final class MapLookupController extends Controller
{
    public function __construct(private readonly PlaceLookup $lookup)
    {
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:200'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'token' => ['nullable', 'string', 'max:64'],
        ]);

        return response()->json([
            'data' => $this->lookup->autocomplete(
                $data['q'],
                isset($data['lat']) ? (float) $data['lat'] : null,
                isset($data['lng']) ? (float) $data['lng'] : null,
                $data['token'] ?? null,
            ),
        ]);
    }

    /**
     * Coordinates for a suggestion that came back without them (Google).
     */
    public function place(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'regex:/^[A-Za-z0-9_\-]{10,300}$/'],
            'token' => ['nullable', 'string', 'max:64'],
        ]);

        return response()->json(['data' => $this->lookup->details($data['id'], $data['token'] ?? null)]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json(['data' => $this->lookup->reverse((float) $data['lat'], (float) $data['lng'])]);
    }
}
