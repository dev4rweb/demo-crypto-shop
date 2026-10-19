<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings', [
            'seo' => page_seo(__('app.admin.settings_title'), __('app.admin.meta_description')),
            'settings' => [
                'coins' => implode(',', ShopSetting::coins()),
                'invoice_ttl_minutes' => ShopSetting::invoiceTtlMinutes(),
                'underpayment_tolerance_percent' => ShopSetting::underpayTolerance(),
                'payment_driver' => ShopSetting::get('payment_driver', config('payments.driver')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'coins' => ['required', 'string', 'max:64'],
            'invoice_ttl_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'underpayment_tolerance_percent' => ['required', 'numeric', 'min:0', 'max:20'],
            'payment_driver' => ['required', 'in:testnet,nowpayments,cryptomus'],
        ]);

        foreach ($data as $key => $value) {
            ShopSetting::put($key, $value);
        }

        return back()->with('success', __('app.common.saved'));
    }
}
