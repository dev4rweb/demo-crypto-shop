<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::query()->with('latestPayment')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('email', 'like', '%'.$q.'%')
                    ->orWhere('uuid', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%');
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Orders', [
            'seo' => page_seo(__('app.admin.orders_title'), __('app.admin.meta_description')),
            'orders' => $orders->through(fn (Order $order) => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'email' => $order->email,
                'name' => $order->name,
                'status' => $order->status,
                'fulfillment_status' => $order->fulfillment_status,
                'total_usd_cents' => $order->total_usd_cents,
                'coin' => $order->latestPayment?->coin,
                'payment_status' => $order->latestPayment?->status,
                'created_at' => $order->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'q' => $request->string('q')->toString() ?: null,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items', 'payments.events', 'promoCode']);

        return Inertia::render('Admin/OrderShow', [
            'seo' => page_seo(__('app.admin.order_title'), __('app.admin.meta_description')),
            'order' => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'email' => $order->email,
                'name' => $order->name,
                'phone' => $order->phone,
                'address' => $order->address,
                'status' => $order->status,
                'fulfillment_status' => $order->fulfillment_status,
                'subtotal_usd_cents' => $order->subtotal_usd_cents,
                'discount_usd_cents' => $order->discount_usd_cents,
                'total_usd_cents' => $order->total_usd_cents,
                'created_at' => $order->created_at?->toIso8601String(),
                'items' => $order->items,
                'payments' => $order->payments,
                'events' => $order->payments->flatMap->events->sortByDesc('id')->values(),
            ],
        ]);
    }

    public function updateFulfillment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:pending,delivered,refunded'],
        ]);

        $order->update(['fulfillment_status' => $data['fulfillment_status']]);

        if ($data['fulfillment_status'] === 'refunded') {
            $order->update(['status' => 'refunded', 'refunded_at' => now()]);
        }

        return back()->with('success', __('app.common.saved'));
    }
}
