<?php

namespace App\Listeners;

use App\Events\LoadPaid;
use App\Notifications\LoadPaidNotification;

final class SendLoadPaidNotification
{
    /**
     * "Payment received" — the dispatcher who booked the load gets notified
     * when it is marked Paid, alongside the commission accrual that
     * CalculateCommissionOnPaid already does for the same event.
     */
    public function handle(LoadPaid $event): void
    {
        $dispatcher = $event->load->dispatcher;

        if ($dispatcher && ($dispatcher->notification_preferences['load_paid'] ?? true)) {
            $dispatcher->notify(new LoadPaidNotification($event->load));
        }
    }
}
