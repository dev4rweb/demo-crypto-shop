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

class CryptomusGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'cryptomus';
    }

    public function createInvoice(Order $order, string $coin, string $amountCrypto, float $rate): PaymentInvoice
    {
        $merchant = (string) config('payments.cryptomus.merchant_id');
        $apiKey = (string) config('payments.cryptomus.api_key');
        $network = (string) config("payments.networks.$coin");
        $address = (string) config("payments.wallets.$coin");
        $externalId = $order->uuid;

        if ($merchant !== '' && $apiKey !== '' && ! str_contains($apiKey, 'your-')) {
            try {
                $payload = [
                    'amount' => number_format($order->total_usd_cents / 100, 2, '.', ''),
                    'currency' => 'USD',
                    'to_currency' => $coin,
                    'order_id' => $order->uuid,
                    'url_callback' => rtrim((string) config('app.url'), '/').'/webhooks/cryptomus',
                ];
                $sign = md5(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)).$apiKey);
                $response = Http::withHeaders([
                    'merchant' => $merchant,
                    'sign' => $sign,
                ])->timeout(12)->post('https://api.cryptomus.com/v1/payment', $payload);

                if ($response->successful()) {
                    $json = $response->json('result') ?? [];
                    $address = (string) ($json['address'] ?? $address);
                    $amountCrypto = (string) ($json['payer_amount'] ?? $amountCrypto);
                    $externalId = (string) ($json['uuid'] ?? $externalId);
                }
            } catch (\Throwable $e) {
                Log::notice('Cryptomus invoice fallback', ['error' => $e->getMessage()]);
            }
        }

        return new PaymentInvoice(
            address: $address,
            amountCrypto: $amountCrypto,
            coin: $coin,
            network: $network,
            qrPayload: strtolower($coin).':'.$address.'?amount='.$amountCrypto,
            externalId: $externalId,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $apiKey = (string) config('payments.cryptomus.api_key');

        if ($apiKey === '' || str_contains($apiKey, 'your-')) {
            return false;
        }

        $data = $request->all();
        $sign = (string) ($data['sign'] ?? '');
        unset($data['sign']);
        $expected = md5(base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES)).$apiKey);

        return $sign !== '' && hash_equals($expected, $sign);
    }

    public function parseWebhook(Request $request): ?WebhookEvent
    {
        $data = $request->all();
        $externalId = (string) ($data['order_id'] ?? $data['uuid'] ?? '');

        if ($externalId === '') {
            return null;
        }

        $providerStatus = strtolower((string) ($data['status'] ?? ''));
        $status = match ($providerStatus) {
            'paid', 'paid_over' => 'confirmed',
            'wrong_amount' => 'underpaid',
            'cancel', 'fail', 'system_fail' => 'expired',
            'confirm_check', 'process' => 'confirming',
            default => 'detected',
        };

        if ($providerStatus === 'paid_over') {
            $status = 'overpaid';
        }

        return new WebhookEvent(
            idempotencyKey: 'cryptomus:'.$externalId.':'.$providerStatus,
            status: $status === 'overpaid' ? 'overpaid' : $status,
            externalId: $externalId,
            txid: $data['txid'] ?? null,
            amountReceived: isset($data['payment_amount']) ? (string) $data['payment_amount'] : null,
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
