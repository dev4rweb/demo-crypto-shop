<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestOrderController extends Controller
{
    public function show(Request $request, string $uuid): Response
    {
        $order = Order::query()
            ->with(['items', 'latestPayment'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_unless($this->canView($request, $order), 403);

        $payment = $order->latestPayment;

        return Inertia::render('Orders/Show', [
            'seo' => page_seo(__('app.guest_order.meta_title'), __('app.account.meta_description')),
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
                    'delivery_payload' => $order->isPaid() ? map_delivery_payload($item->delivery_payload) : null,
                ]),
                'payment' => $payment ? [
                    'uuid' => $payment->uuid,
                    'status' => $payment->status,
                    'coin' => $payment->coin,
                    'amount_crypto' => $payment->amount_crypto,
                ] : null,
            ],
        ]);
    }

    private function canView(Request $request, Order $order): bool
    {
        if ($request->hasValidSignature()) {
            return true;
        }

        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->id === $order->user_id || $user->isStaff();
    }
}
