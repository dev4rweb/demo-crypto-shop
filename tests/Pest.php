<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

function seedCatalog(): Product
{
    $category = Category::query()->create([
        'slug' => 'templates',
        'sort_order' => 1,
    ]);

    return Product::factory()->create([
        'category_id' => $category->id,
        'slug' => 'neon-storefront-kit',
        'price_usd_cents' => 4900,
        'stock' => 20,
    ]);
}

function placeDemoPayment(string $email = 'buyer@example.com'): \App\Models\Payment
{
    $product = Product::query()->where('slug', 'neon-storefront-kit')->first() ?? seedCatalog();

    return app(\App\Services\CheckoutService::class)->place(
        ['name' => 'Jordan Hale', 'email' => $email, 'delivery_type' => 'digital'],
        [['product_id' => $product->id, 'quantity' => 1]],
        'ETH',
    );
}

function confirmDemoPayment(string $email = 'buyer@example.com'): \App\Models\Payment
{
    $payment = placeDemoPayment($email);
    $payment->forceFill(['simulation_started_at' => now()->subSeconds(10)])->save();
    app(\App\Services\PaymentMonitorService::class)->advanceSimulation($payment->fresh('order.items'));

    return $payment->fresh('order.items');
}
