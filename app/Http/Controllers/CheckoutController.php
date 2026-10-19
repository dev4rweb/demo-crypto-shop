<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\PaymentMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Checkout/Index', [
            'seo' => page_seo(__('app.checkout.meta_title'), __('app.checkout.meta_description')),
        ]);
    }

    public function quote(Request $request, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'promo' => ['nullable', 'string', 'max:40'],
        ]);

        $quote = $checkout->quote($data['items'], $data['promo'] ?? null);

        return response()->json([
            'subtotal_usd_cents' => $quote['subtotal'],
            'discount_usd_cents' => $quote['discount'],
            'total_usd_cents' => $quote['total'],
            'promo' => $quote['promo']?->code,
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'delivery_type' => ['required', 'in:digital,physical'],
            'coin' => ['required', 'string', 'max:16'],
            'promo' => ['nullable', 'string', 'max:40'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $payment = $checkout->place(
            [
                'user_id' => $request->user()?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'delivery_type' => $data['delivery_type'],
                'locale' => app()->getLocale(),
            ],
            $data['items'],
            $data['coin'],
            $data['promo'] ?? null,
        );

        return redirect()->to(locale_route('checkout.pay', ['payment' => $payment]));
    }

    public function pay(Payment $payment): Response
    {
        $payment->load('order.items');

        return Inertia::render('Checkout/Pay', [
            'seo' => page_seo(__('app.pay.meta_title'), __('app.pay.meta_description')),
            'payment' => $this->payload($payment),
        ]);
    }

    public function status(Payment $payment, PaymentMonitorService $monitor): JsonResponse
    {
        if ($payment->isOpen()) {
            if ($payment->simulation_started_at) {
                $monitor->advanceSimulation($payment->fresh('order.items'));
            } else {
                $monitor->expireIfNeeded($payment);
            }
            $payment->refresh()->load('order.items');
        }

        return response()->json($this->payload($payment));
    }

    public function simulate(Payment $payment, PaymentMonitorService $monitor): JsonResponse
    {
        $payment = $monitor->startSimulation($payment);

        return response()->json($this->payload($payment));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Payment $payment): array
    {
        $order = $payment->order;

        return [
            'uuid' => $payment->uuid,
            'status' => $payment->status,
            'coin' => $payment->coin,
            'network' => $payment->network,
            'address' => $payment->address,
            'amount_crypto' => $payment->amount_crypto,
            'amount_received' => $payment->amount_received,
            'amount_usd_cents' => $payment->amount_usd_cents,
            'rate_used' => $payment->rate_used,
            'qr_payload' => $payment->qr_payload,
            'confirmations' => $payment->confirmations,
            'required_confirmations' => $payment->required_confirmations,
            'expires_at' => $payment->expires_at?->toIso8601String(),
            'seconds_left' => max(0, ($payment->expires_at?->getTimestamp() ?? time()) - time()),
            'txid' => $payment->txid,
            'driver' => $payment->driver,
            'order_url' => $order ? signed_order_url($order) : null,
            'order' => [
                'uuid' => $order?->uuid,
                'status' => $order?->status,
                'email' => $order?->email,
                'name' => $order?->name,
                'total_usd_cents' => $order?->total_usd_cents,
                'fulfillment_status' => $order?->fulfillment_status,
                'items' => $order?->items->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price_usd_cents' => $item->unit_price_usd_cents,
                    'delivery_payload' => $order->isPaid() ? map_delivery_payload($item->delivery_payload) : null,
                ]),
            ],
        ];
    }
}
