<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Tenant $tenant, public User $admin, public string $setupUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.config('app.name').' workspace is ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant-welcome');
    }
}
