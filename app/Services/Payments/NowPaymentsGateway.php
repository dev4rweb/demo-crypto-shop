<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentInvoice;
use App\Support\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'nowpayments';
    }

    public function createInvoice(Order $order, string $coin, string $amountCrypto, float $rate): PaymentInvoice
    {
        $apiKey = (string) config('payments.nowpayments.api_key');
        $sandbox = (bool) config('payments.nowpayments.sandbox', true);
        $base = $sandbox ? 'https://api-sandbox.nowpayments.io/v1' : 'https://api.nowpayments.io/v1';
        $network = (string) config("payments.networks.$coin");
        $address = (string) config("payments.wallets.$coin");
        $externalId = null;

        if ($apiKey !== '' && ! str_contains($apiKey, 'your-')) {
            try {
                $response = Http::withHeaders(['x-api-key' => $apiKey])
                    ->timeout(12)
                    ->post($base.'/payment', [
                        'price_amount' => $order->total_usd_cents / 100,
                        'price_currency' => 'usd',
                        'pay_currency' => strtolower($coin),
                        'order_id' => $order->uuid,
                        'ipn_callback_url' => rtrim((string) config('app.url'), '/').'/webhooks/nowpayments',
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $address = (string) ($json['pay_address'] ?? $address);
                    $amountCrypto = (string) ($json['pay_amount'] ?? $amountCrypto);
                    $externalId = isset($json['payment_id']) ? (string) $json['payment_id'] : $order->uuid;
                }
            } catch (\Throwable $e) {
                Log::notice('NOWPayments invoice fallback', ['error' => $e->getMessage()]);
            }
        }

        $externalId = $externalId ?: $order->uuid;
        $uri = strtolower($coin).':'.$address.'?amount='.$amountCrypto;

        return new PaymentInvoice(
            address: $address,
            amountCrypto: $amountCrypto,
            coin: $coin,
            network: $network,
            qrPayload: $uri,
            externalId: $externalId,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('payments.nowpayments.ipn_secret');

        if ($secret === '' || str_contains($secret, 'your-')) {
            return false;
        }

        $signature = (string) $request->header('x-nowpayments-sig');
        $payload = $request->all();
        ksort($payload);
        $expected = hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '', $secret);

        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): ?WebhookEvent
    {
        $data = $request->all();
        $externalId = (string) ($data['order_id'] ?? $data['payment_id'] ?? '');

        if ($externalId === '') {
            return null;
        }

        $providerStatus = strtolower((string) ($data['payment_status'] ?? $data['status'] ?? ''));
        $status = match ($providerStatus) {
            'waiting' => 'waiting',
            'confirming' => 'confirming',
            'confirmed', 'sending' => 'confirming',
            'finished', 'paid' => 'confirmed',
            'partially_paid' => 'underpaid',
            'expired', 'failed' => 'expired',
            default => 'detected',
        };

        return new WebhookEvent(
            idempotencyKey: 'nowpayments:'.$externalId.':'.$providerStatus.':'.($data['payment_id'] ?? ''),
            status: $status,
            externalId: $externalId,
            txid: $data['outcome_hash'] ?? $data['txid'] ?? null,
            amountReceived: isset($data['actually_paid']) ? (string) $data['actually_paid'] : null,
            confirmations: (int) ($data['confirmations'] ?? 0),
            payload: $data,
        );
    }

    public function inspect(Payment $payment): array
    {
        return [
            'status' => $payment->status,
            'txid' => $payment->txid,
            'confirmations' => $payment->confirmations,
            'amount_received' => $payment->amount_received,
        ];
    }
}
