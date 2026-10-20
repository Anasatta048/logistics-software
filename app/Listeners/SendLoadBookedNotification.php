<?php

namespace App\Listeners;

use App\Events\LoadBooked;
use App\Notifications\LoadBookedNotification;

final class SendLoadBookedNotification
{
    /** Tell the load's dispatcher (and, if the company wants, the overseers) a load was booked. */
    public function handle(LoadBooked $event): void
    {
        LoadNotifier::send($event->load, 'load_booked', new LoadBookedNotification($event->load));
    }
}
