<?php

namespace App\Notifications;

use App\Models\Load;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LoadBookedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Load $load)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
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
