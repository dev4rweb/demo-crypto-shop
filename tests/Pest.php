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
