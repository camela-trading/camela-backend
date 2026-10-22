<?php


namespace App\Services\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JntService
{
    private function base()
    {
        return config('services.jnt.base_url') . '/api/gateway/v1';
    }

    // Token is valid 7 days, so cache for 6
    public function token()
    {
        return Cache::remember('jnt_token', now()->addDays(6), function () {
            $basic = base64_encode(config('services.jnt.username') . ':' . config('services.jnt.password'));

            $res = Http::withHeaders(['Authorization' => 'Basic ' . $basic])
                ->post($this->base() . '/auth/login')
                ->throw();

            return $res->json('token'); // use the exact field name from your Postman response
        });
    }

    private function http()
    {
        return Http::withHeaders([
            'Authorization' => 'JWT ' . $this->token(),
        ])->acceptJson();
    }

    private function send(string $method, string $url, array $payload = [])
{
    Log::info('JNT request', ['method' => $method, 'url' => $url, 'payload' => $payload]);

    $res = $method === 'post'
        ? $this->http()->post($url, $payload)
        : $this->http()->get($url);

    Log::info('JNT response', [
        'url'    => $url,
        'status' => $res->status(),
        'body'   => $res->json() ?? $res->body(),
    ]);

    return $res->throw()->json();
}

    private function isDomestic($order)
    {
        $country = strtolower(trim($order->shippingAddress->country ?? 'singapore'));
        return in_array($country, ['singapore', 'sg']);
    }

    private function serviceCode($order)
    {
        return $this->isDomestic($order)
            ? config('services.jnt.service_domestic_code')
            : config('services.jnt.service_international_code');
    }

    // Adds +65 if the number has no country prefix (domestic only)
private function formatPhone($phone, $domestic)
{
    $phone = preg_replace('/[^\d+]/', '', (string) $phone);

    if ($domestic && !str_starts_with($phone, '+')) {
        if (str_starts_with($phone, '65') && strlen($phone) === 10) {
            $phone = '+' . $phone;
        } else {
            $phone = '+65' . $phone;
        }
    }

    return $phone;
}

    public function createOrder($order)
    {
        $addr = $order->shippingAddress;
        $domestic = $this->isDomestic($order);
if (blank($order->user->phone)) {
    throw new \RuntimeException('Customer has no phone number. Ask them to add one in their profile.');
}
        $consignee = [
            'contact_name' => $order->user->name,
            'phone_number' => $this->formatPhone($addr->phone ?? $order->user->phone, $domestic),
            'email'        => $order->user->email,
            'address'      => $addr->address,
            'postcode'     => $addr->zip_code,
            'country_code' => $domestic ? 'SG' : $addr->country, // confirm format with J&T
        ];

        if (!$domestic) {
            $consignee['country_name'] = $addr->country;
            $consignee['city']         = $addr->city;
            $consignee['state']        = $addr->state;
        }

        $payload = [
            'reference_number'  => $order->order_number,
            'merchant_code'     => config('services.jnt.merchant_code'),
            'service_code'      => $this->serviceCode($order),
            'consignee_details' => $consignee,
            'pickup_details'    => [ // camela details for pickup
                'contact_name' => 'Camela',
                'phone_number' => '+6580641997',
                'address'      => '14 Arumugam  road..LTC building. Block C #06-05A',
                'postcode'     => '409959',
            ],
            'item_details' => $this->buildItems($order),
        ];

        if (!$domestic) {
            $payload['need_pickup'] = '1';
            $payload['customs_declaration'] = [
                'amount'   => (float) $order->grand_total,
                'currency' => 'SGD',
            ];
        }

        // Add only if the order is cash on delivery:
        // $payload['cod'] = ['amount' => (float) $order->grand_total, 'currency' => 'SGD'];

        // return $this->http()
        //     ->post($this->base() . '/deliveries', $payload)
        //     ->throw()
        //     ->json();

        return $this->send('post', $this->base() . '/deliveries', $payload);
        // returns tracking_id, reference_number, sorting_code (+ awbnumber for international)
    }

    public function label($trackingId)
    {
        // return $this->http()
        //     ->get($this->base() . "/deliveries/connote/{$trackingId}/A6/S1")
        //     ->throw()
        //     ->json();
        return $this->send('get', $this->base() . "/deliveries/connote/{$trackingId}/A6/S1");
    }

    public function track($trackingId)
    {
        // return $this->http()
        //     ->get($this->base() . "/track/{$trackingId}")
        //     ->throw()
        //     ->json();
        return $this->send('get', $this->base() . "/track/{$trackingId}");
    }

public function latestStatus($trackingId): ?string
{
    $res = $this->track($trackingId);

    $status = $res['status'] ?? $res['data']['status'] ?? null;

    if (!$status) {
        $events = $res['events'] ?? $res['data']['events']
            ?? $res['tracking_history'] ?? $res['data']['tracking_history'] ?? [];
        $last = is_array($events) ? end($events) : null;
        $status = is_array($last) ? ($last['status'] ?? null) : null;
    }

    return $status ? strtoupper($status) : null;
}

    private function buildItems($order): array
{
    $order->loadMissing('items.product');

    return $order->items->map(function ($item) {
        $p = $item->product;

        foreach (['length', 'width', 'height'] as $dim) {
            if (blank($p->$dim)) {
                throw new \RuntimeException(
                    "Product \"{$p->title}\" has no {$dim}. Edit the product and add its parcel dimensions."
                );
            }
        }

        return [
            'length'      => (float) $p->length,
            'width'       => (float) $p->width,
            'height'      => (float) $p->height,
            'weight'      => $p->weight !== null ? (float) $p->weight : 500,
            'weight_unit' => $p->weight !== null ? ($p->weight_unit ?? 'G') : 'G',
            'quantity'    => (int) $item->quantity,
            'description' => $p->title,
        ];
    })->values()->all();
}
}