<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\ShopSetting;
use App\Services\Payments\PaymentGatewayManager;
use App\Support\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentMonitorService
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private NotificationService $notifications,
    ) {}

    public function tick(): int
    {
        $updated = 0;

        Payment::query()
            ->with('order.items')
            ->whereIn('status', ['waiting', 'detected', 'confirming', 'underpaid'])
            ->orderBy('id')
            ->get()
            ->each(function (Payment $payment) use (&$updated): void {
                if ($this->expireIfNeeded($payment)) {
                    $updated++;

                    return;
                }

                if ($payment->simulation_started_at) {
                    if ($this->advanceSimulation($payment)) {
                        $updated++;
                    }

                    return;
                }

                $snapshot = $this->gateways->driver($payment->driver)->inspect($payment);
                if ($this->applySnapshot($payment, $snapshot)) {
                    $updated++;
                }
            });

        return $updated;
    }

    public function startSimulation(Payment $payment): Payment
    {
        if (! $payment->isOpen()) {
            return $payment;
        }

        $payment->forceFill(['simulation_started_at' => now()->subSeconds(1)])->save();
        $this->record($payment, 'simulate', [
            'note' => 'Demo simulate payment started',
        ], true, 'simulate:'.$payment->id.':'.now()->timestamp);

        $this->advanceSimulation($payment->fresh('order.items'));

        return $payment->fresh('order.items') ?? $payment;
    }

    public function applyWebhook(Payment $payment, WebhookEvent $event, bool $signatureOk, ?string $ip = null): bool
    {
        $existing = PaymentEvent::query()->where('idempotency_key', $event->idempotencyKey)->first();

        if ($existing) {
            return false;
        }

        $this->record($payment, $event->status, $event->payload, $signatureOk, $event->idempotencyKey, $ip);

        if (! $signatureOk) {
            return false;
        }

        return $this->applySnapshot($payment, [
            'status' => $event->status,
            'txid' => $event->txid,
            'confirmations' => $event->confirmations,
            'amount_received' => $event->amountReceived,
        ]);
    }

    public function expireIfNeeded(Payment $payment): bool
    {
        if (! $payment->isOpen() || ! $payment->expires_at?->isPast()) {
            return false;
        }

        $this->transition($payment, 'expired', [
            'txid' => $payment->txid,
            'confirmations' => $payment->confirmations,
            'amount_received' => $payment->amount_received,
        ]);

        return true;
    }

    public function advanceSimulation(Payment $payment): bool
    {
        if (! $payment->simulation_started_at || ! $payment->isOpen()) {
            return false;
        }

        $elapsed = $payment->simulation_started_at->diffInSeconds(now());
        $need = max(1, $payment->required_confirmations);
        $target = 'waiting';
        $confirmations = 0;

        if ($elapsed >= 8) {
            $target = 'confirmed';
            $confirmations = $need;
        } elseif ($elapsed >= 5) {
            $target = 'confirming';
            $confirmations = $need;
        } elseif ($elapsed >= 3) {
            $target = 'confirming';
            $confirmations = max(1, (int) floor($need / 2));
        } elseif ($elapsed >= 1) {
            $target = 'detected';
        }

        if ($target === $payment->status && $confirmations === $payment->confirmations) {
            return false;
        }

        return $this->applySnapshot($payment, [
            'status' => $target,
            'txid' => $payment->txid ?: 'sim_'.Str::lower(Str::random(16)),
            'confirmations' => $confirmations,
            'amount_received' => $payment->amount_crypto,
        ]);
    }

    /**
     * @param  array{status: string, txid?: ?string, confirmations?: int, amount_received?: ?string}  $snapshot
     */
    public function applySnapshot(Payment $payment, array $snapshot): bool
    {
        $status = $snapshot['status'] ?? $payment->status;

        if ($status === 'waiting' && $payment->status === 'waiting') {
            return false;
        }

        if (in_array($status, ['detected', 'confirming'], true) && isset($snapshot['amount_received'])) {
            $status = $this->classifyAmount($payment, (string) $snapshot['amount_received'], $status);
        }

        if ($status === 'confirmed') {
            $received = (string) ($snapshot['amount_received'] ?? $payment->amount_received ?? $payment->amount_crypto);
            $status = $this->classifyAmount($payment, $received, 'confirmed');
        }

        return $this->transition($payment, $status, $snapshot);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function transition(Payment $payment, string $status, array $snapshot): bool
    {
        $from = $payment->status;

        if ($from === $status && ($snapshot['confirmations'] ?? $payment->confirmations) === $payment->confirmations) {
            return false;
        }

        $payment->status = $status;
        $payment->txid = $snapshot['txid'] ?? $payment->txid;
        $payment->confirmations = (int) ($snapshot['confirmations'] ?? $payment->confirmations);
        $payment->amount_received = $snapshot['amount_received'] ?? $payment->amount_received;

        if (in_array($status, ['detected', 'confirming'], true) && ! $payment->detected_at) {
            $payment->detected_at = now();
        }

        if (in_array($status, ['confirmed', 'overpaid'], true) && ! $payment->confirmed_at) {
            $payment->confirmed_at = now();
        }

        $payment->save();

        $order = $payment->order;
        if (! $order) {
            return true;
        }

        if (in_array($status, ['confirmed', 'overpaid'], true)) {
            $this->markPaid($order, $status === 'overpaid' ? 'overpaid' : 'paid');
        } elseif ($status === 'expired' && $order->status === 'awaiting_payment') {
            $order->update(['status' => 'expired']);
            $this->notifications->orderExpired($order->fresh(['items', 'latestPayment']));
        } elseif ($status === 'underpaid') {
            $order->update(['status' => 'underpaid']);
        }

        $this->record($payment, 'status:'.$status, [
            'from' => $from,
            'to' => $status,
            'snapshot' => $snapshot,
        ], true, 'status:'.$payment->id.':'.$status.':'.$payment->confirmations.':'.now()->timestamp);

        return true;
    }

    private function classifyAmount(Payment $payment, string $received, string $fallback): string
    {
        $expected = (float) $payment->amount_crypto;
        $got = (float) $received;

        if ($expected <= 0) {
            return $fallback;
        }

        $ratio = ($got / $expected) * 100;
        $tolerance = ShopSetting::underpayTolerance();

        if ($got > $expected * 1.005) {
            return $fallback === 'confirmed' ? 'overpaid' : $fallback;
        }

        if ($ratio + 0.0001 < (100 - $tolerance)) {
            return 'underpaid';
        }

        return $fallback === 'detected' ? 'detected' : ($fallback === 'confirming' ? 'confirming' : 'confirmed');
    }

    private function markPaid(Order $order, string $status): void
    {
        if ($order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order, $status): void {
            $order->update([
                'status' => $status,
                'fulfillment_status' => 'delivered',
                'paid_at' => now(),
            ]);
        });

        $this->notifications->paymentConfirmed($order->fresh(['items', 'latestPayment', 'user']));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function record(Payment $payment, string $type, array $payload, bool $signatureOk, string $key, ?string $ip = null): void
    {
        PaymentEvent::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'payment_id' => $payment->id,
                'driver' => $payment->driver,
                'type' => $type,
                'signature_ok' => $signatureOk,
                'payload' => $payload,
                'ip' => $ip,
            ],
        );
    }
}
