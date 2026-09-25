<?php

namespace App\Services\Currency;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CurrencyService
{
    private const CACHE_KEY = 'currency_rates_sgd';
    private const LOCK_KEY = 'currency_rates_sgd_refresh';
    private const RETRY_KEY = 'currency_rates_sgd_retry';
    private const FRESH_HOURS = 6;
    private const RETRY_MINUTES = 10;

    private const CURRENCIES = [
        'SGD' => 'Singapore Dollar',
        'BND' => 'Brunei Dollar',
        'MYR' => 'Malaysian Ringgit',
        'IDR' => 'Indonesian Rupiah',
        'THB' => 'Thai Baht',
        'PHP' => 'Philippine Peso',
        'VND' => 'Vietnamese Dong',
        'KHR' => 'Cambodian Riel',
        'LAK' => 'Lao Kip',
        'MMK' => 'Myanmar Kyat',
        'USD' => 'United States Dollar',
        'CNY' => 'Chinese Yuan',
    ];

    public function rates(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($this->isValidDataset($cached) && $this->isFresh($cached)) {
            return $cached;
        }

        if (Cache::has(self::RETRY_KEY)) {
            return $this->isValidDataset($cached) ? $cached : $this->fallback();
        }

        $lock = Cache::lock(self::LOCK_KEY, 10);

        if (!$lock->get()) {
            return $this->isValidDataset($cached) ? $cached : $this->fallback();
        }

        try {
            $cached = Cache::get(self::CACHE_KEY);

            if ($this->isValidDataset($cached) && $this->isFresh($cached)) {
                return $cached;
            }

            $rates = $this->fetchRates();
            Cache::forever(self::CACHE_KEY, $rates);
            Cache::forget(self::RETRY_KEY);

            return $rates;
        } catch (\Throwable $exception) {
            Cache::put(self::RETRY_KEY, true, now()->addMinutes(self::RETRY_MINUTES));

            Log::warning('Frankfurter currency rate refresh failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->isValidDataset($cached) ? $cached : $this->fallback();
        } finally {
            $lock->release();
        }
    }

    private function fetchRates(): array
    {
        $quotes = array_values(array_diff(array_keys(self::CURRENCIES), ['SGD']));
        $response = Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->retry(2, 200, throw: false)
            ->get('https://api.frankfurter.dev/v2/rates', [
                'base' => 'SGD',
                'quotes' => implode(',', $quotes),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException("Frankfurter returned HTTP {$response->status()}.");
        }

        $rows = $response->json();

        if (!is_array($rows)) {
            throw new RuntimeException('Frankfurter returned malformed JSON.');
        }

        $currencies = [
            'SGD' => [
                'name' => self::CURRENCIES['SGD'],
                'rate' => 1,
            ],
        ];
        $rateDates = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $base = strtoupper((string) ($row['base'] ?? ''));
            $quote = strtoupper((string) ($row['quote'] ?? ''));
            $rate = $row['rate'] ?? null;
            $date = (string) ($row['date'] ?? '');

            if (
                $base !== 'SGD'
                || $quote === 'SGD'
                || !array_key_exists($quote, self::CURRENCIES)
                || !is_numeric($rate)
                || (float) $rate <= 0
                || !$this->isValidDate($date)
            ) {
                continue;
            }

            $currencies[$quote] = [
                'name' => self::CURRENCIES[$quote],
                'rate' => (float) $rate,
            ];
            $rateDates[] = $date;
        }

        if (count($currencies) === 1) {
            throw new RuntimeException('Frankfurter returned no valid SGD exchange rates.');
        }

        return [
            'base' => 'SGD',
            'rate_date' => max($rateDates),
            'fetched_at' => now()->toIso8601String(),
            'currencies' => $currencies,
        ];
    }

    private function fallback(): array
    {
        return [
            'base' => 'SGD',
            'rate_date' => null,
            'fetched_at' => null,
            'currencies' => [
                'SGD' => [
                    'name' => self::CURRENCIES['SGD'],
                    'rate' => 1,
                ],
            ],
        ];
    }

    private function isValidDataset(mixed $dataset): bool
    {
        if (
            !is_array($dataset)
            || ($dataset['base'] ?? null) !== 'SGD'
            || !is_array($dataset['currencies'] ?? null)
            || (float) ($dataset['currencies']['SGD']['rate'] ?? 0) !== 1.0
        ) {
            return false;
        }

        foreach ($dataset['currencies'] as $code => $currency) {
            if (
                !array_key_exists($code, self::CURRENCIES)
                || !is_array($currency)
                || !is_numeric($currency['rate'] ?? null)
                || (float) $currency['rate'] <= 0
            ) {
                return false;
            }
        }

        return true;
    }

    private function isFresh(array $dataset): bool
    {
        if (empty($dataset['fetched_at'])) {
            return false;
        }

        try {
            return Carbon::parse($dataset['fetched_at'])->addHours(self::FRESH_HOURS)->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }

    private function isValidDate(string $date): bool
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $date)?->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }
}
