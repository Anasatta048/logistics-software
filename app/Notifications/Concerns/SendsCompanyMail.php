<?php

namespace App\Notifications\Concerns;

use App\Support\Settings\TenantSettings;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Adds the company's optional email channel to a load notification.
 *
 * Which events email, the subject prefix and the sign-off are company
 * settings. They are read once, when the notification is created inside the
 * request, and carried with it, because the queued email is sent by a worker
 * that has no workspace in context. In-app and live alerts stay immediate;
 * only the email goes through the queue.
 */
trait SendsCompanyMail
{
    public bool $wantsMail = false;

    public ?string $mailSubjectPrefix = null;

    public ?string $mailFooter = null;

    protected function snapshotCompanyMail(string $event): void
    {
        $this->wantsMail = in_array($event, (array) TenantSettings::get('notifications.email_events', []), true);
        $this->mailSubjectPrefix = TenantSettings::get('notifications.email_subject_prefix') ?: null;
        $this->mailFooter = TenantSettings::get('notifications.email_footer') ?: null;
    }

    /** @return list<string> */
    protected function companyChannels(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($this->wantsMail && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync', 'broadcast' => 'sync', 'mail' => config('queue.default')];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        $mail = (new MailMessage())
            ->subject(trim(($this->mailSubjectPrefix ? $this->mailSubjectPrefix.' ' : '').$data['message']))
            ->greeting('Hello '.($notifiable->name ?? ''))
            ->line($data['message'])
            ->action('Open load', $data['url']);

        if ($this->mailFooter) {
            $mail->line($this->mailFooter);
        }

        return $mail;
    }
}
