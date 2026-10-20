<?php

namespace App\Mail;

use App\Models\TenantRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the applicant right after the landing-page form. */
class RequestReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public TenantRequest $tenantRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We received your request');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.request-received');
    }
}
