<?php

namespace App\Listeners;

use App\Events\LoadCancelled;
use App\Notifications\LoadCancelledNotification;

final class SendLoadCancelledNotification
{
    /**
     * The load's dispatcher and everyone who oversees all loads: a cancellation
     * reverses money (commission + voucher) and they are the ones who reconcile it.
     */
    public function handle(LoadCancelled $event): void
    {
        LoadNotifier::send($event->load, 'load_cancelled', new LoadCancelledNotification($event->load, $event->reason), oversight: true);
    }
}
