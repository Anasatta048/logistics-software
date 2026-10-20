<?php

namespace App\Listeners;

use App\Enums\LoadStatus;
use App\Events\LoadStatusChanged;
use App\Notifications\LoadStatusChangedNotification;

final class SendLoadStatusChangedNotification
{
    /**
     * Notify the dispatcher of every status move except into Paid — that
     * transition gets its own, more specific "payment received" notification
     * from SendLoadPaidNotification, so this avoids sending two mails for the
     * same event.
     */
    public function handle(LoadStatusChanged $event): void
    {
        if ($event->to === LoadStatus::Paid) {
            return;
        }

        $dispatcher = $event->load->dispatcher;

        if ($dispatcher && ($dispatcher->notification_preferences['load_status_changed'] ?? true)) {
            $dispatcher->notify(new LoadStatusChangedNotification($event->load, $event->from, $event->to));
        }
    }
}
