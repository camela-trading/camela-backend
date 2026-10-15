<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Shipping\JntService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class JntTest extends Command
{
    protected $signature = 'jnt:test {order_id? : Order ID to book (creates a REAL shipment on that environment)}';
    protected $description = 'Test J&T login, and optionally book an order';

    public function handle(JntService $svc): int
    {
        $base = config('services.jnt.base_url');
        $this->info('Base URL: ' . ($base ?: 'EMPTY (check .env and run config:clear)'));
        $this->info('Username set: ' . (config('services.jnt.username') ? 'yes' : 'NO'));
        $this->info('Password set: ' . (config('services.jnt.password') ? 'yes' : 'NO'));

        if (! $base) {
            return self::FAILURE;
        }

        // 1. Login test (token value is NOT printed, only the field names)
        $basic = base64_encode(config('services.jnt.username') . ':' . config('services.jnt.password'));
        $r = Http::withHeaders(['Authorization' => 'Basic ' . $basic])
            ->post(rtrim($base, '/') . '/api/gateway/v1/auth/login');

        $this->line('Login status: ' . $r->status());
        $json = $r->json();
        if (is_array($json)) {
            $this->line('Top-level fields: ' . implode(', ', array_keys($json)));
            if (isset($json['data']) && is_array($json['data'])) {
                $this->line('data fields: ' . implode(', ', array_keys($json['data'])));
            }
            if (! $r->successful()) {
                $this->error('Body: ' . $r->body());
            }
        } else {
            $this->error('Non-JSON body: ' . mb_substr($r->body(), 0, 500));
        }

        if (! $r->successful()) {
            return self::FAILURE;
        }

        // 2. Optional booking test
        $orderId = $this->argument('order_id');
        if (! $orderId) {
            $this->info('Login OK. Run "php artisan jnt:test <order_id>" to test booking.');
            return self::SUCCESS;
        }

        if (! $this->confirm("Book order #{$orderId} with J&T? This creates a real shipment on {$base}")) {
            return self::SUCCESS;
        }

        Cache::forget('jnt_access_token');

        try {
            $order = Order::findOrFail($orderId);
            $res = $svc->createDelivery($order);
            $this->info('Booking response:');
            $this->line(json_encode($res, JSON_PRETTY_PRINT));
        } catch (RequestException $e) {
            $this->error('J&T rejected the request (HTTP ' . $e->response->status() . '):');
            $this->line($e->response->body());
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}