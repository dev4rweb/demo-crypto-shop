<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * @return array<string, float>
     */
    public function current(): array
    {
        return Cache::remember('crypto_usd_rates', 60, fn () => $this->fetch());
    }

    public function rate(string $coin): float
    {
        $rates = $this->current();
        $coin = strtoupper($coin);

        return (float) ($rates[$coin] ?? config("payments.fallback_rates.$coin", 1));
    }

    public function usdToCrypto(int $usdCents, string $coin): string
    {
        $rate = $this->rate($coin);
        $usd = $usdCents / 100;
        $decimals = (int) config("payments.decimals.$coin", 8);
        $amount = $rate > 0 ? $usd / $rate : 0;

        return number_format($amount, $decimals, '.', '');
    }

    /**
     * Unique tail so two invoices of the same USD total never share an amount.
     */
    public function uniqueAmount(string $baseAmount, string $coin, int $seed): string
    {
        $decimals = (int) config("payments.decimals.$coin", 8);
        $scale = 10 ** $decimals;
        $baseUnits = (int) round((float) $baseAmount * $scale);
        $tailWidth = min(4, $decimals);
        $mod = (10 ** $tailWidth) - 1;
        $tail = 1 + ($seed % $mod);
        $cleared = intdiv($baseUnits, 10 ** $tailWidth) * (10 ** $tailWidth);
        $unique = $cleared + $tail;

        if ($unique <= $baseUnits) {
            $unique += 10 ** $tailWidth;
        }

        return number_format($unique / $scale, $decimals, '.', '');
    }

    /**
     * @return array<string, float>
     */
    private function fetch(): array
    {
        $fallback = config('payments.fallback_rates', []);
        $ids = config('payments.coingecko_ids', []);

        try {
            $response = Http::timeout(8)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => implode(',', array_values($ids)),
                'vs_currencies' => 'usd',
            ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $json = $response->json();
            $rates = [];

            foreach ($ids as $coin => $id) {
                $value = data_get($json, $id.'.usd');
                $rates[$coin] = is_numeric($value) ? (float) $value : (float) ($fallback[$coin] ?? 1);
            }

            return $rates;
        } catch (\Throwable $e) {
            Log::notice('Exchange rate fetch failed, using fallback', ['error' => $e->getMessage()]);

            return $fallback;
        }
    }
}
