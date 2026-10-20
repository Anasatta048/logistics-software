<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\JournalVoucher
 */
class JournalVoucherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jv_number' => $this->jv_number,
            'date' => $this->date->format('Y-m-d'),
            'debit_account' => $this->whenLoaded('debitAccount', fn () => [
                'id' => $this->debitAccount->id,
                'name' => $this->debitAccount->name,
            ]),
            'credit_account' => $this->whenLoaded('creditAccount', fn () => [
                'id' => $this->creditAccount->id,
                'name' => $this->creditAccount->name,
            ]),
            'amount' => (float) $this->amount,
            'narration' => $this->narration,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approved_by' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'load_id' => $this->load_id,
        ];
    }
}
