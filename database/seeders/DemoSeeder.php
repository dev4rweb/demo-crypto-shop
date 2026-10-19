<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('demo123456');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Alex Rivera', 'password' => $password, 'role' => 'admin', 'phone' => '+1 555 0101', 'email_verified_at' => now()],
        );
        $admin->forceFill(['role' => 'admin', 'password' => $password])->save();

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Sam Chen', 'password' => $password, 'role' => 'manager', 'phone' => '+1 555 0102', 'email_verified_at' => now()],
        );
        $manager->forceFill(['role' => 'manager', 'password' => $password])->save();

        $buyer = User::query()->updateOrCreate(
            ['email' => 'buyer@example.com'],
            ['name' => 'Jordan Hale', 'password' => $password, 'role' => 'customer', 'phone' => '+1 555 0148', 'email_verified_at' => now()],
        );
        $buyer->forceFill(['role' => 'customer', 'password' => $password])->save();

        $categories = [
            ['slug' => 'templates', 'sort_order' => 1],
            ['slug' => 'licenses', 'sort_order' => 2],
            ['slug' => 'courses', 'sort_order' => 3],
            ['slug' => 'gift-cards', 'sort_order' => 4],
        ];

        $bySlug = [];
        foreach ($categories as $row) {
            $bySlug[$row['slug']] = Category::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }

        $catalog = [
            [
                'slug' => 'neon-storefront-kit',
                'category' => 'templates',
                'type' => 'template',
                'price' => 4900,
                'stock' => 80,
                'featured' => true,
                'bento' => 'lg',
                'image' => '/images/products/neon-storefront.svg',
                'variants' => [
                    ['name' => 'Standard', 'price' => 4900, 'stock' => 80],
                    ['name' => 'Extended', 'price' => 8900, 'stock' => 40],
                ],
            ],
            [
                'slug' => 'terminal-dashboard',
                'category' => 'templates',
                'type' => 'template',
                'price' => 7900,
                'stock' => 60,
                'featured' => true,
                'bento' => 'md',
                'image' => '/images/products/terminal-dashboard.svg',
            ],
            [
                'slug' => 'orbit-landing',
                'category' => 'templates',
                'type' => 'template',
                'price' => 3900,
                'stock' => 100,
                'featured' => false,
                'bento' => 'sm',
                'image' => '/images/products/orbit-landing.svg',
            ],
            [
                'slug' => 'motion-pack',
                'category' => 'templates',
                'type' => 'template',
                'price' => 5900,
                'stock' => 70,
                'featured' => false,
                'bento' => 'sm',
                'image' => '/images/products/motion-pack.svg',
            ],
            [
                'slug' => 'cipher-pro-license',
                'category' => 'licenses',
                'type' => 'license',
                'price' => 19900,
                'stock' => 25,
                'featured' => true,
                'bento' => 'md',
                'image' => '/images/products/cipher-pro.svg',
            ],
            [
                'slug' => 'ledger-sync',
                'category' => 'licenses',
                'type' => 'license',
                'price' => 8900,
                'stock' => 40,
                'featured' => false,
                'bento' => 'sm',
                'image' => '/images/products/ledger-sync.svg',
            ],
            [
                'slug' => 'onchain-analytics-course',
                'category' => 'courses',
                'type' => 'course',
                'price' => 12900,
                'stock' => 200,
                'featured' => true,
                'bento' => 'md',
                'image' => '/images/products/analytics-course.svg',
            ],
            [
                'slug' => 'checkout-masterclass',
                'category' => 'courses',
                'type' => 'course',
                'price' => 9900,
                'stock' => 200,
                'featured' => false,
                'bento' => 'sm',
                'image' => '/images/products/checkout-course.svg',
            ],
            [
                'slug' => 'gift-50',
                'category' => 'gift-cards',
                'type' => 'gift_card',
                'price' => 5000,
                'stock' => 500,
                'featured' => false,
                'bento' => 'sm',
                'image' => '/images/products/gift-50.svg',
            ],
            [
                'slug' => 'gift-100',
                'category' => 'gift-cards',
                'type' => 'gift_card',
                'price' => 10000,
                'stock' => 500,
                'featured' => false,
                'bento' => 'sm',
                'image' => '/images/products/gift-100.svg',
            ],
        ];

        foreach ($catalog as $row) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'category_id' => $bySlug[$row['category']]->id,
                    'type' => $row['type'],
                    'price_usd_cents' => $row['price'],
                    'stock' => $row['stock'],
                    'is_digital' => true,
                    'is_featured' => $row['featured'],
                    'bento_size' => $row['bento'],
                    'image' => $row['image'],
                    'gallery' => [$row['image']],
                    'delivery_payload' => [
                        'key' => 'NEXUS-'.strtoupper(substr($row['slug'], 0, 4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)),
                        'url' => 'https://demo-crypto-shop.dev4rweb.com/downloads/'.$row['slug'],
                    ],
                    'is_active' => true,
                ],
            );

            if (! empty($row['variants'])) {
                foreach ($row['variants'] as $variant) {
                    ProductVariant::query()->updateOrCreate(
                        ['product_id' => $product->id, 'name' => $variant['name']],
                        [
                            'sku' => strtoupper($row['slug'].'-'.$variant['name']),
                            'price_usd_cents' => $variant['price'],
                            'stock' => $variant['stock'],
                            'delivery_payload' => [
                                'key' => 'NEXUS-'.strtoupper($variant['name']).'-'.strtoupper(Str::random(8)),
                                'url' => 'https://demo-crypto-shop.dev4rweb.com/downloads/'.$row['slug'],
                            ],
                        ],
                    );
                }
            }
        }

        PromoCode::query()->updateOrCreate(
            ['code' => 'NEXUS10'],
            ['type' => 'percent', 'value' => 10, 'max_uses' => 200, 'is_active' => true, 'expires_at' => now()->addYear()],
        );
        PromoCode::query()->updateOrCreate(
            ['code' => 'WELCOME5'],
            ['type' => 'fixed', 'value' => 500, 'max_uses' => 500, 'is_active' => true, 'expires_at' => now()->addMonths(6)],
        );

        ShopSetting::put('coins', 'USDT,BTC,ETH');
        ShopSetting::put('invoice_ttl_minutes', 30);
        ShopSetting::put('underpayment_tolerance_percent', 1);
        ShopSetting::put('payment_driver', 'testnet');

        $this->seedOrders($buyer);
    }

    private function seedOrders(User $buyer): void
    {
        $products = Product::query()->with('variants')->get();
        if ($products->isEmpty()) {
            return;
        }

        $states = [
            ['status' => 'paid', 'pay' => 'confirmed', 'days' => 1, 'coin' => 'USDT'],
            ['status' => 'paid', 'pay' => 'confirmed', 'days' => 2, 'coin' => 'BTC'],
            ['status' => 'awaiting_payment', 'pay' => 'waiting', 'days' => 0, 'coin' => 'ETH'],
            ['status' => 'expired', 'pay' => 'expired', 'days' => 3, 'coin' => 'USDT'],
            ['status' => 'underpaid', 'pay' => 'underpaid', 'days' => 1, 'coin' => 'BTC'],
            ['status' => 'overpaid', 'pay' => 'overpaid', 'days' => 4, 'coin' => 'ETH'],
            ['status' => 'awaiting_payment', 'pay' => 'confirming', 'days' => 0, 'coin' => 'USDT'],
        ];

        foreach ($states as $i => $state) {
            $product = $products[$i % $products->count()];
            $created = now()->subDays($state['days'])->subHours($i + 1);
            $uuid = '00000000-0000-4000-8000-'.str_pad((string) ($i + 1), 12, '0', STR_PAD_LEFT);

            $order = Order::query()->updateOrCreate(
                ['uuid' => $uuid],
                [
                    'user_id' => $buyer->id,
                    'email' => $buyer->email,
                    'name' => $buyer->name,
                    'phone' => '+1 555 0148',
                    'delivery_type' => 'digital',
                    'status' => $state['status'],
                    'fulfillment_status' => in_array($state['status'], ['paid', 'overpaid'], true) ? 'delivered' : 'pending',
                    'subtotal_usd_cents' => $product->price_usd_cents,
                    'discount_usd_cents' => 0,
                    'total_usd_cents' => $product->price_usd_cents,
                    'locale' => 'en',
                    'paid_at' => in_array($state['status'], ['paid', 'overpaid'], true) ? $created->copy()->addMinutes(12) : null,
                    'created_at' => $created,
                    'updated_at' => $created,
                ],
            );

            OrderItem::query()->updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id],
                [
                    'name' => $product->slug,
                    'quantity' => 1,
                    'unit_price_usd_cents' => $product->price_usd_cents,
                    'delivery_payload' => $product->delivery_payload,
                ],
            );

            $amount = match ($state['coin']) {
                'BTC' => '0.000'.(5100 + $i),
                'ETH' => '0.014'.(200 + $i),
                default => number_format($product->price_usd_cents / 100, 6, '.', ''),
            };

            $payment = Payment::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'driver' => 'testnet',
                    'coin' => $state['coin'],
                    'network' => config('payments.networks.'.$state['coin']),
                    'address' => 'TSEED'.strtoupper(substr(md5($uuid), 0, 28)),
                    'amount_crypto' => $amount,
                    'amount_received' => in_array($state['pay'], ['confirmed', 'overpaid', 'underpaid'], true) ? $amount : null,
                    'amount_usd_cents' => $product->price_usd_cents,
                    'rate_used' => config('payments.fallback_rates.'.$state['coin']),
                    'status' => $state['pay'],
                    'txid' => in_array($state['pay'], ['waiting', 'expired'], true) ? null : 'seed_tx_'.$i,
                    'confirmations' => $state['pay'] === 'confirming' ? 1 : ($state['pay'] === 'confirmed' || $state['pay'] === 'overpaid' ? 3 : 0),
                    'required_confirmations' => 3,
                    'qr_payload' => 'tron:seed'.$i.'?amount='.$amount,
                    'external_id' => $uuid,
                    'expires_at' => $state['pay'] === 'waiting' ? now()->addMinutes(25) : $created->copy()->addMinutes(30),
                    'detected_at' => in_array($state['pay'], ['waiting', 'expired'], true) ? null : $created->copy()->addMinutes(4),
                    'confirmed_at' => in_array($state['pay'], ['confirmed', 'overpaid'], true) ? $created->copy()->addMinutes(12) : null,
                ],
            );

            PaymentEvent::query()->updateOrCreate(
                ['idempotency_key' => 'seed:'.$uuid.':'.$state['pay']],
                [
                    'payment_id' => $payment->id,
                    'driver' => 'testnet',
                    'type' => 'seed:'.$state['pay'],
                    'signature_ok' => true,
                    'payload' => ['seed' => true, 'status' => $state['pay']],
                    'ip' => '203.0.113.10',
                ],
            );
        }
    }
}
