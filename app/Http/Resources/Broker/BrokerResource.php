<?php

namespace App\Http\Resources\Broker;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Broker
 */
class BrokerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'broker_mc_number' => $this->broker_mc_number,
            'credit_notes' => $this->credit_notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
