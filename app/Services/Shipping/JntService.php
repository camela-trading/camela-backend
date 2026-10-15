<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class JntService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.jnt.base_url'), '/');
    }

    private function token(bool $fresh = false): string
    {
        if ($fresh) {
            Cache::forget('jnt_access_token');
        }

        // Token is valid for 7 days, so cache it for 6
        return Cache::remember('jnt_access_token', now()->addDays(6), function () {
            $basic = base64_encode(
                config('services.jnt.username') . ':' . config('services.jnt.password')
            );

            $response = Http::withHeaders(['Authorization' => 'Basic ' . $basic])
                ->post($this->baseUrl() . '/api/gateway/v1/auth/login');

            $response->throw();

            // The docs don't show the exact response field, so try the common ones
            $token = $response->json('access_token')
                ?? $response->json('token')
                ?? $response->json('data.access_token')
                ?? $response->json('data.token');

            if (! $token) {
                throw new \RuntimeException('J&T login returned no token: ' . $response->body());
            }

            return $token;
        });
    }

    /**
     * Create a domestic J&T delivery for a paid order.
     * Returns ['tracking_id' => ..., 'reference_number' => ..., 'sorting_code' => ...]
     */
    public function createDelivery(Order $order): array
    {
        $order->loadMissing(['items.product', 'user', 'shippingAddress']);

        $store = StoreSetting::firstOrFail();
        $address = $order->shippingAddress;

        if (! $address) {
            throw new \RuntimeException('Order has no shipping address.');
        }

        // One parcel for the whole order. Assumes products.weight is in Kg.
        $totalWeight = 0;
        $names = [];
        foreach ($order->items as $item) {
            $totalWeight += (float) ($item->product->weight ?? 0) * $item->quantity;
            $names[] = ($item->product->title ?? 'Item') . ' x' . $item->quantity;
        }
        $totalWeight = max($totalWeight, 0.1);

        $payload = [
            'reference_number' => $order->order_number,
            'service_code' => config('services.jnt.service_code'),
            'pickup_details' => [
                'contact_name' => $store->store_name,
                'phone_number' => $store->phone,
                'address' => $store->address,
                'postcode' => config('services.jnt.pickup_postcode'),
            ],
            'consignee_details' => [
                'contact_name' => $order->user->name,
                'phone_number' => $order->user->phone,
                'email' => $order->user->email,
                'address' => $address->address,
                'postcode' => $address->zip_code,
                'country_code' => 'SG',
            ],
            'item_details' => [[
                'weight' => round($totalWeight, 3),
                'weight_unit' => 'Kg',
                'quantity' => 1,
                'description' => mb_substr(implode(', ', $names), 0, 200),
            ]],
        ];

        if (config('services.jnt.merchant_code')) {
            $payload['merchant_code'] = config('services.jnt.merchant_code');
        }

        $response = $this->send($payload, $this->token());

        // Token may have expired: log in again and retry once
        if ($response->status() === 401) {
            $response = $this->send($payload, $this->token(true));
        }

        $response->throw();

        return $response->json();
    }

    private function send(array $payload, string $token)
    {
        return Http::withHeaders(['Authorization' => 'JWT ' . $token])
            ->acceptJson()
            ->post($this->baseUrl() . '/api/gateway/v1/deliveries', $payload);
    }
}