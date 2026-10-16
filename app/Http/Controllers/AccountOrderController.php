<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()->with('latestPayment')->latest()->get();

        return Inertia::render('Account/Orders', [
            'seo' => page_seo(__('app.account.meta_title'), __('app.account.meta_description')),
            'orders' => $orders->map(fn (Order $order) => [
                'uuid' => $order->uuid,
                'status' => $order->status,
                'total_usd_cents' => $order->total_usd_cents,
                'created_at' => $order->created_at?->toIso8601String(),
                'coin' => $order->latestPayment?->coin,
                'payment_status' => $order->latestPayment?->status,
                'payment_id' => $order->latestPayment?->id,
            ]),
        ]);
    }

    public function show(Request $request, string $uuid): Response
    {
        $order = $request->user()->orders()->with(['items', 'latestPayment'])->where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Account/Show', [
            'seo' => page_seo(__('app.account.order_title'), __('app.account.meta_description')),
            'order' => [
                'uuid' => $order->uuid,
                'status' => $order->status,
                'fulfillment_status' => $order->fulfillment_status,
                'email' => $order->email,
                'total_usd_cents' => $order->total_usd_cents,
                'discount_usd_cents' => $order->discount_usd_cents,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price_usd_cents' => $item->unit_price_usd_cents,
                    'delivery_payload' => $order->isPaid() ? $item->delivery_payload : null,
                ]),
                'payment' => $order->latestPayment ? [
                    'id' => $order->latestPayment->id,
                    'status' => $order->latestPayment->status,
                    'coin' => $order->latestPayment->coin,
                    'amount_crypto' => $order->latestPayment->amount_crypto,
                ] : null,
            ],
        ]);
    }
}
