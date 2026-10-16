<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Promos', [
            'seo' => page_seo(__('app.admin.promos_title'), __('app.admin.meta_description')),
            'promos' => PromoCode::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PromoCode::query()->create($this->validated($request));

        return back()->with('success', __('app.common.saved'));
    }

    public function update(Request $request, PromoCode $promo): RedirectResponse
    {
        $promo->update($this->validated($request, $promo));

        return back()->with('success', __('app.common.saved'));
    }

    public function destroy(PromoCode $promo): RedirectResponse
    {
        $promo->delete();

        return back()->with('success', __('app.common.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PromoCode $promo = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:promo_codes,code,'.($promo?->id ?? 'NULL')],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
