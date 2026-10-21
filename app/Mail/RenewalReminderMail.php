<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** To a workspace whose trial or paid period is about to end (or just did, inside the grace days). */
class RenewalReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Tenant $tenant, public int $daysLeft, public bool $trial)
    {
    }

    public function envelope(): Envelope
    {
        $what = $this->trial ? 'trial' : 'subscription';

        return new Envelope(subject: $this->daysLeft > 0
            ? "Your {$what} ends in {$this->daysLeft} day".($this->daysLeft === 1 ? '' : 's')
            : "Your {$what} has ended: renew to keep access");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.renewal-reminder');
    }
}
