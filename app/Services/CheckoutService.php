<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Models\ShopSetting;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private ExchangeRateService $rates,
        private PaymentGatewayManager $gateways,
        private NotificationService $notifications,
    ) {}

    /**
     * @param  list<array{product_id: int, variant_id?: int|null, quantity: int}>  $items
     * @return array{subtotal: int, discount: int, total: int, promo: ?PromoCode}
     */
    public function quote(array $items, ?string $promoCode = null): array
    {
        $lines = $this->resolveLines($items);
        $subtotal = 0;

        foreach ($lines as $line) {
            $subtotal += $line['unit_price_usd_cents'] * $line['quantity'];
        }

        $promo = $this->findPromo($promoCode);
        $discount = $promo ? $promo->discountCents($subtotal) : 0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'promo' => $promo,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  list<array{product_id: int, variant_id?: int|null, quantity: int}>  $items
     */
    public function place(array $customer, array $items, string $coin, ?string $promoCode = null): Payment
    {
        $coin = strtoupper($coin);
        $allowed = ShopSetting::coins();

        if (! in_array($coin, $allowed, true)) {
            throw ValidationException::withMessages(['coin' => 'Unsupported coin']);
        }

        $quote = $this->quote($items, $promoCode);

        if ($quote['total'] <= 0 || $quote['lines'] === []) {
            throw ValidationException::withMessages(['items' => 'Cart is empty']);
        }

        return DB::transaction(function () use ($customer, $quote, $coin) {
            $order = Order::query()->create([
                'user_id' => $customer['user_id'] ?? null,
                'promo_code_id' => $quote['promo']?->id,
                'email' => $customer['email'],
                'name' => $customer['name'],
                'phone' => $customer['phone'] ?? null,
                'address' => $customer['address'] ?? null,
                'delivery_type' => $customer['delivery_type'] ?? 'digital',
                'status' => 'awaiting_payment',
                'fulfillment_status' => 'pending',
                'subtotal_usd_cents' => $quote['subtotal'],
                'discount_usd_cents' => $quote['discount'],
                'total_usd_cents' => $quote['total'],
                'locale' => $customer['locale'] ?? app()->getLocale(),
            ]);

            foreach ($quote['lines'] as $line) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price_usd_cents' => $line['unit_price_usd_cents'],
                    'delivery_payload' => $line['delivery_payload'],
                ]);

                if ($line['variant']) {
                    $line['variant']->decrement('stock', $line['quantity']);
                } else {
                    $line['product']->decrement('stock', $line['quantity']);
                }
            }

            if ($quote['promo']) {
                $quote['promo']->increment('uses');
            }

            $rate = $this->rates->rate($coin);
            $base = $this->rates->usdToCrypto($quote['total'], $coin);
            $amount = $this->rates->uniqueAmount($base, $coin, $order->id);
            $gateway = $this->gateways->driver();
            $invoice = $gateway->createInvoice($order, $coin, $amount, $rate);
            $ttl = ShopSetting::invoiceTtlMinutes();

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'driver' => $gateway->driver(),
                'coin' => $coin,
                'network' => $invoice->network,
                'address' => $invoice->address,
                'amount_crypto' => $invoice->amountCrypto,
                'amount_usd_cents' => $quote['total'],
                'rate_used' => $rate,
                'status' => 'waiting',
                'required_confirmations' => (int) config("payments.required_confirmations.$coin", 1),
                'qr_payload' => $invoice->qrPayload,
                'external_id' => $invoice->externalId,
                'expires_at' => now()->addMinutes($ttl),
                'meta' => $invoice->meta,
            ]);

            $this->notifications->orderCreated($order->fresh(['items', 'latestPayment']));

            return $payment->fresh('order');
        });
    }

    /**
     * @param  list<array{product_id: int, variant_id?: int|null, quantity: int}>  $items
     * @return list<array<string, mixed>>
     */
    private function resolveLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $product = Product::query()->where('is_active', true)->find($item['product_id'] ?? 0);

            if (! $product) {
                throw ValidationException::withMessages(['items' => 'Unknown product']);
            }

            $variant = null;
            $price = $product->price_usd_cents;
            $stock = $product->stock;
            $name = $product->localizedName();
            $payload = $product->delivery_payload;

            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->find($item['variant_id']);

                if (! $variant) {
                    throw ValidationException::withMessages(['items' => 'Unknown variant']);
                }

                $price = $variant->price_usd_cents;
                $stock = $variant->stock;
                $name .= ' — '.$variant->name;
                $payload = $variant->delivery_payload ?: $payload;
            }

            if ($stock < $qty) {
                throw ValidationException::withMessages(['items' => 'Not enough stock for '.$name]);
            }

            $lines[] = [
                'product' => $product,
                'variant' => $variant,
                'name' => $name,
                'quantity' => $qty,
                'unit_price_usd_cents' => $price,
                'delivery_payload' => $payload,
            ];
        }

        return $lines;
    }

    private function findPromo(?string $code): ?PromoCode
    {
        if (! $code) {
            return null;
        }

        $promo = PromoCode::query()->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        if (! $promo || ! $promo->isUsable()) {
            throw ValidationException::withMessages(['promo' => 'Promo code is not valid']);
        }

        return $promo;
    }
}
