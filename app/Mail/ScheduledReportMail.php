<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A scheduled report, attached. Sent straight from the scheduler (the file is too big to queue). */
class ScheduledReportMail extends Mailable
{
    public function __construct(
        public string $reportName,
        public string $content,
        public string $filename,
        public string $mime,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->reportName.' — '.now()->format(df()));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.scheduled-report');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->content, $this->filename)->withMime($this->mime)];
    }
}
