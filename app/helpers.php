<?php

if (! function_exists('locale_route')) {
    /**
     * Named route that keeps the current locale prefix when present.
     *
     * @param  array<string, mixed>  $params
     */
    function locale_route(string $name, array $params = [], bool $absolute = true): string
    {
        $locale = request()->route('locale');

        if (is_string($locale) && $locale !== '') {
            return route('locale.'.$name, ['locale' => $locale] + $params, $absolute);
        }

        return route($name, $params, $absolute);
    }
}

if (! function_exists('telegram_enabled')) {
    function telegram_enabled(): bool
    {
        $token = (string) config('services.telegram.bot_token');

        return $token !== '' && $token !== 'your_bot_token_here';
    }
}

if (! function_exists('telegram_connect_ready')) {
    function telegram_connect_ready(): bool
    {
        $username = ltrim((string) config('services.telegram.bot_username'), '@');

        return telegram_enabled() && $username !== '' && $username !== 'your_bot_username';
    }
}

if (! function_exists('page_seo')) {
    /**
     * @return array{title: string, description: string}
     */
    function page_seo(?string $title = null, ?string $description = null): array
    {
        return [
            'title' => $title ?: (string) __('app.meta.title'),
            'description' => $description ?: (string) __('app.meta.description'),
        ];
    }
}

if (! function_exists('format_usd')) {
    function format_usd(int $cents): string
    {
        return '$'.number_format($cents / 100, 2, '.', ',');
    }
}

if (! function_exists('signed_order_url')) {
    function signed_order_url(\App\Models\Order $order): string
    {
        $locale = $order->locale;
        $supported = config('localization.supported', []);

        if (is_string($locale) && $locale !== '' && in_array($locale, $supported, true)) {
            return Illuminate\Support\Facades\URL::signedRoute('locale.orders.show', [
                'locale' => $locale,
                'uuid' => $order->uuid,
            ]);
        }

        return Illuminate\Support\Facades\URL::signedRoute('orders.show', [
            'uuid' => $order->uuid,
        ]);
    }
}

if (! function_exists('signed_download_url')) {
    function signed_download_url(string $slug): string
    {
        return Illuminate\Support\Facades\URL::signedRoute('downloads.show', [
            'slug' => $slug,
        ]);
    }
}

if (! function_exists('public_download_url')) {
    function public_download_url(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (preg_match('#/downloads/([A-Za-z0-9_-]+)#', $url, $matches)) {
            return signed_download_url($matches[1]);
        }

        return $url;
    }
}

if (! function_exists('map_delivery_payload')) {
    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    function map_delivery_payload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        if (! empty($payload['url']) && is_string($payload['url'])) {
            $payload['url'] = public_download_url($payload['url']);
        }

        return $payload;
    }
}
