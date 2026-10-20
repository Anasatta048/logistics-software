<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Sign-in, sign-out and failed sign-in attempts, with the IP and browser so an
 * admin can tell a colleague from a stranger.
 */
final class LogAuthActivity
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function handleLogin(Login $event): void
    {
        $this->record($event->user, 'login', 'Signed in');
    }

    public function handleLogout(Logout $event): void
    {
        $this->record($event->user, 'logout', 'Signed out');
    }

    public function handleFailed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? 'unknown');
        $user = $event->user instanceof User ? $event->user : null;

        $this->logger->log(
            config('activity.auth_module'),
            'login_failed',
            "Failed sign-in attempt for {$email}",
            $user,
            $email,
            actor: $user,
        );
    }

    private function record(?object $user, string $action, string $text): void
    {
        if (! $user instanceof User) {
            return;
        }

        $this->logger->log(config('activity.auth_module'), $action, $text, $user, $user->email, actor: $user);
    }
}
