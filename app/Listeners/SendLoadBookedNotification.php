<?php

namespace App\Listeners;

use App\Events\LoadBooked;
use App\Notifications\LoadBookedNotification;

final class SendLoadBookedNotification
{
    /**
     * Phase 9: notify the load's dispatcher the moment a load is booked.
     */
    public function handle(LoadBooked $event): void
    {
        $dispatcher = $event->load->dispatcher;

        if ($dispatcher && ($dispatcher->notification_preferences['load_booked'] ?? true)) {
            $dispatcher->notify(new LoadBookedNotification($event->load));
        }
    }
}
