<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\PaymentMonitorService;
use App\Services\Payments\CryptomusGateway;
use App\Services\Payments\NowPaymentsGateway;
use App\Services\Payments\TestnetGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function nowpayments(Request $request, NowPaymentsGateway $gateway, PaymentMonitorService $monitor): JsonResponse
    {
        return $this->ingest($request, $gateway, $monitor, 'nowpayments');
    }

    public function cryptomus(Request $request, CryptomusGateway $gateway, PaymentMonitorService $monitor): JsonResponse
    {
        return $this->ingest($request, $gateway, $monitor, 'cryptomus');
    }

    public function testnet(Request $request, TestnetGateway $gateway, PaymentMonitorService $monitor): JsonResponse
    {
        return $this->ingest($request, $gateway, $monitor, 'testnet');
    }

    private function ingest(Request $request, $gateway, PaymentMonitorService $monitor, string $driver): JsonResponse
    {
        $ok = $gateway->verifyWebhook($request);
        $event = $gateway->parseWebhook($request);

        if (! $event) {
            PaymentEvent::query()->create([
                'driver' => $driver,
                'type' => 'invalid',
                'idempotency_key' => $driver.':invalid:'.sha1($request->getContent().microtime()),
                'signature_ok' => $ok,
                'payload' => $request->all(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false], 422);
        }

        $payment = Payment::query()
            ->where('driver', $driver)
            ->where(function ($q) use ($event) {
                $q->where('external_id', $event->externalId)
                    ->orWhereHas('order', fn ($order) => $order->where('uuid', $event->externalId));
            })
            ->latest('id')
            ->first();

        if (! $payment) {
            PaymentEvent::query()->firstOrCreate(
                ['idempotency_key' => $event->idempotencyKey],
                [
                    'driver' => $driver,
                    'type' => 'unmatched',
                    'signature_ok' => $ok,
                    'payload' => $event->payload,
                    'ip' => $request->ip(),
                ],
            );

            return response()->json(['ok' => true, 'matched' => false]);
        }

        $applied = $monitor->applyWebhook($payment, $event, $ok, $request->ip());

        return response()->json(['ok' => true, 'applied' => $applied, 'signature_ok' => $ok]);
    }
}
