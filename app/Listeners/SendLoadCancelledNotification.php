<?php

namespace App\Listeners;

use App\Events\LoadCancelled;
use App\Models\User;
use App\Notifications\LoadCancelledNotification;

final class SendLoadCancelledNotification
{
    /**
     * The load's own dispatcher and every Admin are notified — Admins because
     * a cancellation reverses money (commission + JV) and they are the ones
     * who reconcile it.
     */
    public function handle(LoadCancelled $event): void
    {
        $notification = new LoadCancelledNotification($event->load, $event->reason);

        $dispatcher = $event->load->dispatcher;

        if ($dispatcher && ($dispatcher->notification_preferences['load_cancelled'] ?? true)) {
            $dispatcher->notify($notification);
        }

        User::permission('loads.view-all')
            ->where('id', '!=', $event->load->dispatcher_id)
            ->get()
            ->filter(fn (User $admin) => $admin->notification_preferences['load_cancelled'] ?? true)
            ->each(fn (User $admin) => $admin->notify($notification));
    }
}
