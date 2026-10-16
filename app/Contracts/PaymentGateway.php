<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentInvoice;
use App\Support\WebhookEvent;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function driver(): string;

    public function createInvoice(Order $order, string $coin, string $amountCrypto, float $rate): PaymentInvoice;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): ?WebhookEvent;

    /**
     * Inspect on-chain / provider status for an open invoice.
     *
     * @return array{status: string, txid?: ?string, confirmations?: int, amount_received?: ?string}
     */
    public function inspect(Payment $payment): array;
}
