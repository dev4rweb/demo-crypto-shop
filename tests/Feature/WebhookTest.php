<?php

use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\Payments\NowPaymentsGateway;
use Illuminate\Http\Request;

function nowpaymentsSignature(array $payload, string $secret): string
{
    ksort($payload);

    return hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
}

test('nowpayments webhook with valid signature marks the invoice paid', function () {
    $product = seedCatalog();
    $payment = app(CheckoutService::class)->place(
        ['name' => 'Jordan Hale', 'email' => 'buyer@example.com', 'delivery_type' => 'digital'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'USDT',
    );
    $payment->update(['driver' => 'nowpayments', 'external_id' => $payment->order->uuid]);

    $payload = [
        'payment_id' => 'np_1',
        'order_id' => $payment->order->uuid,
        'payment_status' => 'finished',
        'actually_paid' => $payment->amount_crypto,
        'confirmations' => 3,
        'outcome_hash' => '0xabc',
    ];
    $sig = nowpaymentsSignature($payload, 'test-nowpayments-secret');

    $this->postJson('/webhooks/nowpayments', $payload, [
        'x-nowpayments-sig' => $sig,
    ])->assertOk()->assertJsonPath('applied', true)->assertJsonPath('signature_ok', true);

    expect($payment->fresh()->status)->toBe('confirmed')
        ->and($payment->order->fresh()->status)->toBe('paid')
        ->and($payment->order->fresh()->fulfillment_status)->toBe('delivered');
});

test('nowpayments webhook with bad signature is stored and does not pay', function () {
    $product = seedCatalog();
    $payment = app(CheckoutService::class)->place(
        ['name' => 'Jordan Hale', 'email' => 'buyer@example.com', 'delivery_type' => 'digital'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'USDT',
    );
    $payment->update(['driver' => 'nowpayments', 'external_id' => $payment->order->uuid]);

    $payload = [
        'payment_id' => 'np_bad',
        'order_id' => $payment->order->uuid,
        'payment_status' => 'finished',
        'actually_paid' => $payment->amount_crypto,
    ];

    $this->postJson('/webhooks/nowpayments', $payload, [
        'x-nowpayments-sig' => 'deadbeef',
    ])->assertOk()->assertJsonPath('signature_ok', false);

    expect($payment->fresh()->status)->toBe('waiting')
        ->and($payment->order->fresh()->status)->toBe('awaiting_payment')
        ->and(\App\Models\PaymentEvent::query()->where('signature_ok', false)->count())->toBeGreaterThan(0);
});

test('duplicate webhook is idempotent', function () {
    $product = seedCatalog();
    $payment = app(CheckoutService::class)->place(
        ['name' => 'Jordan Hale', 'email' => 'buyer@example.com', 'delivery_type' => 'digital'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'USDT',
    );
    $payment->update(['driver' => 'nowpayments', 'external_id' => $payment->order->uuid]);

    $payload = [
        'payment_id' => 'np_dup',
        'order_id' => $payment->order->uuid,
        'payment_status' => 'finished',
        'actually_paid' => $payment->amount_crypto,
        'confirmations' => 3,
    ];
    $headers = ['x-nowpayments-sig' => nowpaymentsSignature($payload, 'test-nowpayments-secret')];

    $this->postJson('/webhooks/nowpayments', $payload, $headers)->assertJsonPath('applied', true);
    $this->postJson('/webhooks/nowpayments', $payload, $headers)->assertJsonPath('applied', false);

    expect(Payment::query()->count())->toBe(1)
        ->and(\App\Models\PaymentEvent::query()->where('idempotency_key', 'like', 'nowpayments:%')->count())->toBe(1);
});

test('nowpayments signature helper matches the gateway', function () {
    $payload = ['order_id' => 'abc', 'payment_status' => 'finished'];
    $sig = nowpaymentsSignature($payload, 'test-nowpayments-secret');
    $request = Request::create('/webhooks/nowpayments', 'POST', $payload);
    $request->headers->set('x-nowpayments-sig', $sig);

    expect(app(NowPaymentsGateway::class)->verifyWebhook($request))->toBeTrue();
});
