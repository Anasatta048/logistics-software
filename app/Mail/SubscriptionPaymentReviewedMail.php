<?php

namespace App\Mail;

use App\Models\SubscriptionPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** To the workspace: its reported payment was approved or rejected. */
class SubscriptionPaymentReviewedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public SubscriptionPayment $payment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->payment->isApproved() ? 'Payment received, thank you' : 'We could not confirm your payment');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-reviewed');
    }
}
