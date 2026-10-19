<?php


namespace App\Services\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class JntService
{
    private function base()
    {
        return env('JNT_BASE_URL') . '/api/gateway/v1';
    }

    // Token is valid 7 days, so cache for 6
    public function token()
    {
        return Cache::remember('jnt_token', now()->addDays(6), function () {
            $basic = base64_encode(env('JNT_USERNAME') . ':' . env('JNT_PASSWORD'));

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

    private function isDomestic($order)
    {
        $country = strtolower(trim($order->shippingAddress->country ?? 'singapore'));
        return in_array($country, ['singapore', 'sg']);
    }

    private function serviceCode($order)
    {
        return $this->isDomestic($order)
            ? env('JNT_SERVICE_DOMESTIC_CODE')
            : env('JNT_SERVICE_INTERNATIONAL_INTERNATIONAL_CODE');
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

        $consignee = [
            'contact_name' => $order->user->name,
            'phone_number' => $this->formatPhone($order->user->phone, $domestic),
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
            'merchant_code'     => env('JNT_MERCHANT_CODE'),
            'service_code'      => $this->serviceCode($order),
            'consignee_details' => $consignee,
            'pickup_details'    => [ // camela details for pickup
                'contact_name' => 'Camela',
                'phone_number' => '+6580641997',
                'address'      => '14 Arumugam  road..LTC building. Block C #06-05A',
                'postcode'     => '409959',
            ],
            'item_details' => [[
                'weight'      => 500,
                'weight_unit' => 'G',
                'quantity'    => 1,
                'description' => 'Order ' . $order->order_number,
            ]],
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

        return $this->http()
            ->post($this->base() . '/deliveries', $payload)
            ->throw()
            ->json();
        // returns tracking_id, reference_number, sorting_code (+ awbnumber for international)
    }

    public function label($trackingId)
    {
        return $this->http()
            ->get($this->base() . "/deliveries/connote/{$trackingId}/A6/S1")
            ->throw()
            ->json();
    }

    public function track($trackingId)
    {
        return $this->http()
            ->get($this->base() . "/track/{$trackingId}")
            ->throw()
            ->json();
    }
}