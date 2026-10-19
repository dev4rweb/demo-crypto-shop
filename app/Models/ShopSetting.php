<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ShopSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('shop_settings')) {
                return $default;
            }

            $settings = Cache::remember('shop_settings', 30, function () {
                return static::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable) {
            return $default;
        }

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : (string) $value]);
        Cache::forget('shop_settings');
    }

    /**
     * @return list<string>
     */
    public static function coins(): array
    {
        $raw = static::get('coins', implode(',', config('payments.coins', ['USDT', 'BTC', 'ETH'])));

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }

    public static function invoiceTtlMinutes(): int
    {
        return (int) static::get('invoice_ttl_minutes', config('payments.invoice_ttl_minutes', 30));
    }

    public static function underpayTolerance(): float
    {
        return (float) static::get('underpayment_tolerance_percent', config('payments.underpayment_tolerance_percent', 1));
    }
}
