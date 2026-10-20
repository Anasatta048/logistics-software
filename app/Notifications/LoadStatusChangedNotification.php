<?php

namespace App\Notifications;

use App\Enums\LoadStatus;
use App\Models\Load;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LoadStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Load $load,
        public readonly LoadStatus $from,
        public readonly LoadStatus $to,
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
            'type' => 'load_status_changed',
            'load_id' => $this->load->id,
            'reference_number' => $this->load->reference_number,
            'url' => route('loads.edit', $this->load),
            'from' => $this->from->value,
            'to' => $this->to->value,
            'message' => "Load {$this->load->reference_number} is now {$this->to->label()}.",
        ];
    }
}
