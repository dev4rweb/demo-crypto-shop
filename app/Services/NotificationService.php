<?php

namespace App\Services;

use App\Mail\OrderCreatedMail;
use App\Mail\PaymentConfirmedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    public function __construct(
        private TelegramService $telegram,
    ) {}

    public function orderCreated(Order $order): void
    {
        try {
            Mail::to($order->email)->send(new OrderCreatedMail($order));
        } catch (Throwable $e) {
            Log::error('Failed to send OrderCreatedMail', [
                'order_uuid' => $order->uuid,
                'email' => $order->email,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function paymentConfirmed(Order $order): void
    {
        try {
            Mail::to($order->email)->send(new PaymentConfirmedMail($order));
        } catch (Throwable $e) {
            Log::error('Failed to send PaymentConfirmedMail', [
                'order_uuid' => $order->uuid,
                'email' => $order->email,
                'message' => $e->getMessage(),
            ]);
        }

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
