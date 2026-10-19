<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.mail.created_subject', ['number' => substr($this->order->uuid, 0, 8)], $this->order->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-created',
            with: [
                'order' => $this->order,
                'orderUrl' => signed_order_url($this->order),
            ],
        );
    }
}
