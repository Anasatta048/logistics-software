<?php

namespace App\Notifications;

use App\Models\Load;
use App\Notifications\Concerns\SendsCompanyMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class LoadBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SendsCompanyMail;

    public function __construct(public readonly Load $load)
    {
        $this->snapshotCompanyMail('load_booked');
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
            'type' => 'load_booked',
            'load_id' => $this->load->id,
            'reference_number' => $this->load->reference_number,
            'url' => route('loads.edit', $this->load),
            'message' => "Load {$this->load->reference_number} was booked.",
        ];
    }
}
