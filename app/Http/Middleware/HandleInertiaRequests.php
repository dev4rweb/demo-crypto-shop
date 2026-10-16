<?php

namespace App\Http\Middleware;

use App\Models\ShopSetting;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $rtl = config('localization.rtl', ['ar']);
        $hidden = config('localization.hidden_in_selector', []);

        $selectorLocales = collect(config('localization.supported', []))
            ->reject(fn (string $code): bool => in_array($code, $hidden, true))
            ->map(fn (string $code): array => [
                'code' => $code,
                'name' => config("localization.names.$code", $code),
                'flag' => config("localization.flag_codes.$code"),
            ])
            ->values()
            ->all();

        $user = $request->user();

        $rates = [];
        try {
            $rates = app(ExchangeRateService::class)->current();
        } catch (\Throwable) {
            $rates = config('payments.fallback_rates', []);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_staff' => $user->isStaff(),
                    'telegram_connected' => (bool) $user->telegram_chat_id,
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'locale' => $locale,
            'routeLocale' => $request->route('locale'),
            'dir' => in_array($locale, $rtl, true) ? 'rtl' : 'ltr',
            'translations' => trans('app'),
            'locales' => $selectorLocales,
            'telegramEnabled' => telegram_connect_ready(),
            'paymentDriver' => ShopSetting::get('payment_driver', (string) config('payments.driver')),
            'coins' => ShopSetting::coins(),
            'rates' => $rates,
            'demoLogins' => [
                ['role' => 'admin', 'email' => 'admin@example.com', 'password' => 'demo123456'],
                ['role' => 'manager', 'email' => 'manager@example.com', 'password' => 'demo123456'],
                ['role' => 'buyer', 'email' => 'buyer@example.com', 'password' => 'demo123456'],
            ],
        ];
    }
}
