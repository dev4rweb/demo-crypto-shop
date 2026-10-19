<?php

namespace App\Services;

use App\Mail\OrderCreatedMail;
use App\Mail\PaymentConfirmedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private TelegramService $telegram,
    ) {}

    public function orderCreated(Order $order): void
    {
        Mail::to($order->email)->send(new OrderCreatedMail($order));
    }

    public function paymentConfirmed(Order $order): void
    {
        Mail::to($order->email)->send(new PaymentConfirmedMail($order));

        $this->broadcastStaff(
            __('app.telegram.paid', [
                'number' => $order->uuid,
                'amount' => format_usd($order->total_usd_cents),
                'coin' => $order->latestPayment?->coin ?? '',
            ], $order->locale)
        );
    }

    public function orderExpired(Order $order): void
    {
        $this->broadcastStaff(
            __('app.telegram.expired', [
                'number' => $order->uuid,
                'amount' => format_usd($order->total_usd_cents),
            ], $order->locale)
        );
    }

    private function broadcastStaff(string $text): void
    {
        User::query()
            ->whereIn('role', ['admin', 'manager'])
            ->whereNotNull('telegram_chat_id')
            ->pluck('telegram_chat_id')
            ->unique()
            ->each(fn ($chatId) => $this->telegram->sendMessage((string) $chatId, $text));
    }
}
