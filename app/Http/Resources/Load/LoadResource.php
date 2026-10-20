<?php

namespace App\Http\Resources\Load;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Load
 */
class LoadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'company' => $this->whenLoaded('company', fn () => $this->company === null ? null : [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'mc_number' => $this->company->mc_number,
            ]),
            'broker' => $this->whenLoaded('broker', fn () => [
                'id' => $this->broker->id,
                'company_name' => $this->broker->company_name,
                'broker_mc_number' => $this->broker->broker_mc_number,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver === null ? null : [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
                'truck_number' => $this->driver->truck_number,
            ]),
            // The load's own copy of the driver's name, set when the driver was
            // typed in rather than picked.
            'driver_name' => $this->driver_name,
            'driver_label' => $this->driverLabel(),
            'dispatcher' => $this->whenLoaded('dispatcher', fn () => $this->dispatcher === null ? null : [
                'id' => $this->dispatcher->id,
                'name' => $this->dispatcher->name,
            ]),
            'equipment_type' => $this->equipment_type,
            'origin' => [
                'address' => $this->origin_address,
                'lat' => $this->origin_lat,
                'lng' => $this->origin_lng,
            ],
            'destination' => [
                'address' => $this->destination_address,
                'lat' => $this->destination_lat,
                'lng' => $this->destination_lng,
            ],
            'total_miles' => (float) $this->total_miles,
            'weight_lbs' => (float) $this->weight_lbs,
            'pickup_at' => $this->pickup_at->toIso8601String(),
            'dropoff_at' => $this->dropoff_at->toIso8601String(),
            'rate' => (float) $this->rate,
            'rate_per_mile' => (float) $this->rate_per_mile,
            'company_commission_percentage' => (float) $this->company_commission_percentage,
            'company_commission_amount' => (float) $this->company_commission_amount,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'cancellation' => [
                'reason' => $this->cancellation_reason,
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'cancelled_by' => $this->whenLoaded('cancelledBy', fn () => $this->cancelledBy === null ? null : [
                    'id' => $this->cancelledBy->id,
                    'name' => $this->cancelledBy->name,
                ]),
            ],
            'notes' => $this->notes,
        ];
    }
}
