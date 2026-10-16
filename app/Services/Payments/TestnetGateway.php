<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentInvoice;
use App\Support\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestnetGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'testnet';
    }

    public function createInvoice(Order $order, string $coin, string $amountCrypto, float $rate): PaymentInvoice
    {
        $address = $this->uniqueAddress($coin, $order->uuid);
        $network = (string) config("payments.networks.$coin");
        $uri = $this->uri($coin, $address, $amountCrypto);

        return new PaymentInvoice(
            address: $address,
            amountCrypto: $amountCrypto,
            coin: $coin,
            network: $network,
            qrPayload: $uri,
            externalId: $order->uuid,
            meta: ['unique_address' => true],
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('payments.nowpayments.ipn_secret', 'testnet-demo');
        $signature = (string) $request->header('X-Testnet-Signature', $request->header('x-signature'));
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): ?WebhookEvent
    {
        $data = $request->all();
        $externalId = (string) ($data['order_id'] ?? $data['payment_id'] ?? '');

        if ($externalId === '') {
            return null;
        }

        return new WebhookEvent(
            idempotencyKey: (string) ($data['idempotency_key'] ?? ('testnet:'.$externalId.':'.($data['status'] ?? 'update'))),
            status: (string) ($data['status'] ?? 'detected'),
            externalId: $externalId,
            txid: $data['txid'] ?? null,
            amountReceived: isset($data['amount']) ? (string) $data['amount'] : null,
            confirmations: (int) ($data['confirmations'] ?? 0),
            payload: $data,
        );
    }

    public function inspect(Payment $payment): array
    {
        // Public explorers are optional; demo path uses Simulate payment.
        // Returning current status keeps the invoice waiting until simulation or webhook.
        return [
            'status' => $payment->status,
            'txid' => $payment->txid,
            'confirmations' => $payment->confirmations,
            'amount_received' => $payment->amount_received,
        ];
    }

    private function uniqueAddress(string $coin, string $seed): string
    {
        $hash = substr(hash('sha256', $coin.'|'.$seed.'|nexus'), 0, 40);

        return match ($coin) {
            'BTC' => 'tb1q'.substr($hash, 0, 32),
            'ETH' => '0x'.substr($hash, 0, 40),
            default => 'T'.strtoupper(substr($hash, 0, 33)),
        };
    }

    private function uri(string $coin, string $address, string $amount): string
    {
        return match ($coin) {
            'BTC' => 'bitcoin:'.$address.'?amount='.$amount,
            'ETH' => 'ethereum:'.$address.'?value='.$amount,
            default => 'tron:'.$address.'?amount='.$amount.'&token=USDT',
        };
    }
}
