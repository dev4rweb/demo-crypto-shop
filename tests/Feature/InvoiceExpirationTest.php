<?php

use App\Services\CheckoutService;
use App\Services\PaymentMonitorService;

test('open invoices expire after the lifetime', function () {
    $product = seedCatalog();
    $payment = app(CheckoutService::class)->place(
        ['name' => 'Jordan Hale', 'email' => 'buyer@example.com', 'delivery_type' => 'digital'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'BTC',
    );

    $payment->forceFill(['expires_at' => now()->subMinute()])->save();

    app(PaymentMonitorService::class)->tick();

    expect($payment->fresh()->status)->toBe('expired')
        ->and($payment->order->fresh()->status)->toBe('expired');
});

test('simulate payment walks waiting to confirmed and releases keys', function () {
    $product = seedCatalog();
    $payment = app(CheckoutService::class)->place(
        ['name' => 'Jordan Hale', 'email' => 'buyer@example.com', 'delivery_type' => 'digital'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'ETH',
    );

    $this->postJson('/pay/'.$payment->uuid.'/simulate')->assertOk();

    $fresh = $payment->fresh();
    expect($fresh->simulation_started_at)->not->toBeNull()
        ->and($fresh->status)->toBe('detected');

    $this->travel(10)->seconds();
    app(PaymentMonitorService::class)->advanceSimulation($fresh->fresh('order.items'));

    $done = $payment->fresh('order.items');
    expect($done->status)->toBe('confirmed')
        ->and($done->order->status)->toBe('paid')
        ->and($done->order->items->first()->delivery_payload['key'] ?? null)->not->toBeNull();
});
