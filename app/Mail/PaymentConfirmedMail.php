<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.mail.paid_subject', ['number' => substr($this->order->uuid, 0, 8)], $this->order->locale),
        );
    }

    public function content(): Content
    {
        $order = $this->order->loadMissing('items');

        return new Content(
            view: 'emails.payment-confirmed',
            with: [
                'order' => $order,
                'orderUrl' => signed_order_url($order),
            ],
        );
    }
}
