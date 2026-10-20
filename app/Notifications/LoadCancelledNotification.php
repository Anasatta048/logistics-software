<?php

namespace App\Notifications;

use App\Models\Load;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LoadCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Load $load,
        public readonly string $reason,
    ) {
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
            'type' => 'load_cancelled',
            'load_id' => $this->load->id,
            'reference_number' => $this->load->reference_number,
            'url' => route('loads.edit', $this->load),
            'reason' => $this->reason,
            'message' => "Load {$this->load->reference_number} was cancelled: {$this->reason}",
        ];
    }
}
