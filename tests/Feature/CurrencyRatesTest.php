<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyRatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_rates_endpoint_normalizes_only_allowed_currencies_and_always_includes_sgd(): void
    {
        $quotes = ['BND', 'MYR', 'IDR', 'THB', 'PHP', 'VND', 'KHR', 'LAK', 'MMK', 'USD', 'CNY'];
        $rows = array_map(fn (string $quote, int $index) => [
            'date' => '2026-08-11',
            'base' => 'SGD',
            'quote' => $quote,
            'rate' => $index + 1.25,
        ], $quotes, array_keys($quotes));
        $rows[] = ['date' => '2026-08-11', 'base' => 'SGD', 'quote' => 'EUR', 'rate' => 0.67];

        Http::fake([
            'api.frankfurter.dev/*' => Http::response($rows),
        ]);

        $response = $this->getJson('/api/currencies')
            ->assertSuccessful()
            ->assertJsonPath('base', 'SGD')
            ->assertJsonPath('currencies.SGD.rate', 1)
            ->assertJsonMissingPath('currencies.EUR');

        $this->assertEqualsCanonicalizing(
            ['SGD', ...$quotes],
            array_keys($response->json('currencies'))
        );

        $this->getJson('/api/currencies')->assertSuccessful();

        Http::assertSent(fn (Request $request) =>
            str_starts_with($request->url(), 'https://api.frankfurter.dev/v2/rates?')
            && $request['base'] === 'SGD'
            && $request['quotes'] === implode(',', $quotes)
        );
        Http::assertSentCount(1);
    }

    public function test_invalid_frankfurter_response_is_rejected_with_safe_sgd_fallback(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                ['date' => 'invalid', 'base' => 'USD', 'quote' => 'PHP', 'rate' => 0],
            ]),
        ]);

        $this->getJson('/api/currencies')
            ->assertSuccessful()
            ->assertJsonPath('currencies.SGD.rate', 1)
            ->assertJsonCount(1, 'currencies');
    }

    public function test_last_valid_rates_survive_a_later_frankfurter_failure(): void
    {
        Http::fakeSequence()
            ->push([
                ['date' => '2026-08-11', 'base' => 'SGD', 'quote' => 'CNY', 'rate' => 5.61],
            ])
            ->push([], 500)
            ->push([], 500);

        $this->getJson('/api/currencies')
            ->assertJsonPath('currencies.CNY.rate', 5.61);

        $this->travel(7)->hours();

        $this->getJson('/api/currencies')
            ->assertSuccessful()
            ->assertJsonPath('currencies.CNY.rate', 5.61);
    }

    public function test_no_cache_http_failure_returns_sgd_only(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([], 500),
        ]);

        $this->getJson('/api/currencies')
            ->assertSuccessful()
            ->assertJsonPath('base', 'SGD')
            ->assertJsonPath('currencies.SGD.rate', 1)
            ->assertJsonCount(1, 'currencies');
    }
}
