<?php

namespace App\Mail;

use App\Models\SubscriptionPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** To the platform owner: a workspace reported a payment to review. */
class SubscriptionPaymentSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public SubscriptionPayment $payment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment to review: '.$this->payment->tenant->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-submitted');
    }
}
