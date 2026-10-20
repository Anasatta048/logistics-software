<?php

namespace App\Listeners;

use App\Events\LoadPaid;
use App\Notifications\LoadPaidNotification;

final class SendLoadPaidNotification
{
    /** "Payment received": alongside the commission accrual CalculateCommissionOnPaid does for the same event. */
    public function handle(LoadPaid $event): void
    {
        LoadNotifier::send($event->load, 'load_paid', new LoadPaidNotification($event->load));
    }
}
