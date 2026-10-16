<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Mail;

test('guest can place a digital order and get a waiting invoice', function () {
    Mail::fake();
    $product = seedCatalog();

    $this->get('/')->assertOk();

    $response = $this->post('/checkout', [
        'name' => 'Jordan Hale',
        'email' => 'buyer@example.com',
        'phone' => '+1 555 0148',
        'delivery_type' => 'digital',
        'coin' => 'USDT',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $payment = Payment::query()->first();
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe('waiting')
        ->and($payment->coin)->toBe('USDT')
        ->and($payment->amount_usd_cents)->toBe(4900);

    $response->assertRedirect(route('checkout.pay', $payment));

    $order = Order::query()->first();
    expect($order->email)->toBe('buyer@example.com')
        ->and($order->status)->toBe('awaiting_payment')
        ->and($order->items)->toHaveCount(1);

    $this->get('/pay/'.$payment->id)->assertOk();
});

test('checkout rejects empty cart', function () {
    $this->from('/checkout')->post('/checkout', [
        'name' => 'Jordan Hale',
        'email' => 'buyer@example.com',
        'delivery_type' => 'digital',
        'coin' => 'USDT',
        'items' => [],
    ])->assertSessionHasErrors();
});

test('quote endpoint returns discounted total', function () {
    $product = seedCatalog();
    \App\Models\PromoCode::query()->create([
        'code' => 'NEXUS10',
        'type' => 'percent',
        'value' => 10,
        'is_active' => true,
    ]);

    $this->postJson('/checkout/quote', [
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
        'promo' => 'NEXUS10',
    ])->assertOk()
        ->assertJsonPath('subtotal_usd_cents', 9800)
        ->assertJsonPath('discount_usd_cents', 980)
        ->assertJsonPath('total_usd_cents', 8820);
});
