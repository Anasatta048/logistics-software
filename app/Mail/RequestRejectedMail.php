<?php

namespace App\Mail;

use App\Models\TenantRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public TenantRequest $tenantRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'About your '.config('app.name').' request');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.request-rejected');
    }
}
