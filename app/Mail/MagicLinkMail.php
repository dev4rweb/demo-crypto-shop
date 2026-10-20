<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use SerializesModels;

    public function __construct(public string $loginUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.auth.magic_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            with: ['loginUrl' => $this->loginUrl],
        );
    }
}
