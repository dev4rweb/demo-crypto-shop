<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $slug = 'demo-'.Str::random(8);

        return [
            'category_id' => Category::query()->first()?->id ?? Category::query()->create([
                'slug' => 'templates',
                'sort_order' => 1,
            ])->id,
            'slug' => $slug,
            'type' => 'template',
            'price_usd_cents' => 4900,
            'stock' => 50,
            'is_digital' => true,
            'is_featured' => false,
            'bento_size' => 'sm',
            'image' => '/images/products/neon-storefront.svg',
            'delivery_payload' => [
                'key' => 'NEXUS-DEMO-'.strtoupper(Str::random(8)),
                'url' => 'https://demo-crypto-shop.dev4rweb.com/downloads/'.$slug,
            ],
            'is_active' => true,
        ];
    }
}
