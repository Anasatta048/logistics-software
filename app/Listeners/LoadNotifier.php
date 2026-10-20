<?php

namespace App\Listeners;

use App\Models\Load;
use App\Models\User;
use App\Support\Settings\TenantSettings;
use Illuminate\Notifications\Notification;

/**
 * Who hears about a load event: the load's dispatcher (if they have not
 * switched that alert off), and, when the company wants it or the event is
 * serious (a cancellation reverses money), everyone who oversees all loads.
 */
final class LoadNotifier
{
    public static function send(Load $load, string $event, Notification $notification, bool $oversight = false): void
    {
        $dispatcher = $load->dispatcher;

        if ($dispatcher && ($dispatcher->notification_preferences[$event] ?? true)) {
            $dispatcher->notify($notification);
        }

        if (! $oversight && ! TenantSettings::on('notifications.oversight')) {
            return;
        }

        User::permission('loads.view-all')
            ->where('id', '!=', $load->dispatcher_id)
            ->get()
            ->filter(fn (User $user): bool => $user->notification_preferences[$event] ?? true)
            ->each(fn (User $user) => $user->notify($notification));
    }
}
