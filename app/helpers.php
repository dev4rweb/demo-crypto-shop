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
