<?php

use App\Services\CheckoutService;
use App\Services\ExchangeRateService;

test('unique crypto amounts differ for two orders of the same usd total', function () {
    $product = seedCatalog();
    $checkout = app(CheckoutService::class);

    $first = $checkout->place(
        ['name' => 'A', 'email' => 'a@example.com', 'delivery_type' => 'digital', 'locale' => 'en'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'USDT',
    );

    $second = $checkout->place(
        ['name' => 'B', 'email' => 'b@example.com', 'delivery_type' => 'digital', 'locale' => 'en'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'USDT',
    );

    expect($first->amount_usd_cents)->toBe($second->amount_usd_cents)
        ->and($first->amount_crypto)->not->toBe($second->amount_crypto)
        ->and($first->address)->not->toBe($second->address);
});

test('usd to crypto uses cached rate and unique tail stays close to the quote', function () {
    $rates = app(ExchangeRateService::class);
    $base = $rates->usdToCrypto(4900, 'USDT');
    $unique = $rates->uniqueAmount($base, 'USDT', 42);

    expect((float) $base)->toBe(49.0)
        ->and((float) $unique)->toBeGreaterThan((float) $base)
        ->and((float) $unique - (float) $base)->toBeLessThan(0.02);
});

test('percent promo reduces the invoice usd total', function () {
    $product = seedCatalog();
    \App\Models\PromoCode::query()->create([
        'code' => 'SAVE20',
        'type' => 'percent',
        'value' => 20,
        'is_active' => true,
    ]);

    $quote = app(CheckoutService::class)->quote(
        [['product_id' => $product->id, 'quantity' => 1]],
        'SAVE20',
    );

    expect($quote['subtotal'])->toBe(4900)
        ->and($quote['discount'])->toBe(980)
        ->and($quote['total'])->toBe(3920);
});
