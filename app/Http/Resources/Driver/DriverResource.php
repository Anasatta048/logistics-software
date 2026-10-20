<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Driver
 */
class DriverResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            // The Load form reads the company and its default commission off
            // the picked driver so it never has to guess (Section 4.1).
            'company_name' => $this->whenLoaded('company', fn () => $this->company?->name),
            'company_mc_number' => $this->whenLoaded('company', fn () => $this->company?->mc_number),
            // The company's earning: only for people allowed to see commission.
            'company_default_commission_percentage' => $this->when(
                $request->user()?->can('view-company-commission') && $this->relationLoaded('company'),
                fn () => $this->company?->default_commission_percentage,
            ),
            'name' => $this->name,
            'truck_number' => $this->truck_number,
            'trailer_number' => $this->trailer_number,
            'open_loads' => $this->openLoadsSummary(),
            'equipment_type' => $this->equipment_type,
            'truck_length_ft' => $this->truck_length_ft,
            'truck_width_ft' => $this->truck_width_ft,
            'truck_height_ft' => $this->truck_height_ft,
            'truck_max_weight_lbs' => $this->truck_max_weight_lbs,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
        ];
    }
}
