<?php

namespace App\Notifications;

use App\Models\Load;
use App\Notifications\Concerns\SendsCompanyMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class LoadPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SendsCompanyMail;

    public function __construct(public readonly Load $load)
    {
        $this->snapshotCompanyMail('load_paid');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->companyChannels($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'load_paid',
            'load_id' => $this->load->id,
            'reference_number' => $this->load->reference_number,
            'url' => route('loads.edit', $this->load),
            'message' => "Payment received for load {$this->load->reference_number}.",
        ];
    }
}
