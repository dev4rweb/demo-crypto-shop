<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $from = Carbon::parse($request->string('from', now()->subDays(14)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->string('to', now()->toDateString()))->endOfDay();

        $orders = Order::query()->whereBetween('created_at', [$from, $to]);
        $paid = (clone $orders)->whereIn('status', ['paid', 'overpaid']);

        $byCoin = Payment::query()
            ->whereIn('status', ['confirmed', 'overpaid'])
            ->whereBetween('confirmed_at', [$from, $to])
            ->get()
            ->groupBy('coin')
            ->map(fn ($rows) => [
                'count' => $rows->count(),
                'usd_cents' => $rows->sum('amount_usd_cents'),
                'crypto' => $rows->sum(fn ($p) => (float) $p->amount_crypto),
            ]);

        $chart = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $chart[] = [
                'date' => $day->toDateString(),
                'orders' => Order::query()->whereDate('created_at', $day)->count(),
                'paid_usd_cents' => Order::query()->whereIn('status', ['paid', 'overpaid'])->whereDate('paid_at', $day)->sum('total_usd_cents'),
            ];
        }

        $created = (clone $orders)->count();
        $paidCount = $paid->count();

        return Inertia::render('Admin/Dashboard', [
            'seo' => page_seo(__('app.admin.dashboard_title'), __('app.admin.meta_description')),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'stats' => [
                'orders' => $created,
                'paid' => $paidCount,
                'revenue_usd_cents' => $paid->sum('total_usd_cents'),
                'conversion' => $created > 0 ? round($paidCount / $created * 100, 1) : 0,
            ],
            'byCoin' => $byCoin,
            'chart' => $chart,
        ]);
    }
}
