<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentController extends Controller
{
public function create(Request $request)
{
    $request->validate(['order_id' => 'required|integer']);

    $order = Order::with('items.product')
        ->where('id', $request->order_id)
        ->where('user_id', $request->user()->id)
        ->firstOrFail();

    Stripe::setApiKey(config('services.stripe.secret'));

    $currency = 'sgd';
    $lineItems = [];
    $itemsTotal = 0; // cents

    // 1. One row per product
    foreach ($order->items as $item) {
        $product = $item->product;
        $unitAmount = (int) round($item->price * 100);
        $itemsTotal += $unitAmount * $item->quantity;

        $productData = [
            'name' => $product->name ?? 'Product #' . $item->product_id,
        ];

        $image = $product->image ?? null; // adjust to your Product column
        if ($image) {
            $productData['images'] = [
                str_starts_with($image, 'http') ? $image : asset('storage/' . ltrim($image, '/')),
            ];
        }

        $lineItems[] = [
            'quantity' => $item->quantity,
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $unitAmount,
                'product_data' => $productData,
            ],
        ];
    }

    // 2. Tax as its own row
    $tax = (int) round($order->tax * 100);
    if ($tax > 0) {
        $lineItems[] = [
            'quantity' => 1,
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $tax,
                'product_data' => ['name' => 'Tax'],
            ],
        ];
    }

    // 3. Shipping as a shipping option
    $shipping = (int) round($order->shipping_fee * 100);
    $shippingOptions = [[
        'shipping_rate_data' => [
            'type' => 'fixed_amount',
            'display_name' => 'Shipping',
            'fixed_amount' => ['amount' => $shipping, 'currency' => $currency],
        ],
    ]];

    // 4. Discount as a coupon (Stripe doesn't allow negative line items)
    $discount = (int) round($order->discount * 100);
    $discounts = [];
    if ($discount > 0) {
        $coupon = \Stripe\Coupon::create([
            'amount_off' => $discount,
            'currency' => $currency,
            'duration' => 'once',
            'name' => 'Discount',
        ]);
        $discounts[] = ['coupon' => $coupon->id];
    }

    // Safety check: Stripe's total must match what the customer saw
    $expected = $itemsTotal + $tax + $shipping - $discount;
    $grandTotal = (int) round($order->grand_total * 100);
    if ($expected !== $grandTotal) {
        \Log::warning('Stripe total mismatch', [
            'order_id' => $order->id,
            'expected' => $expected,
            'grand_total' => $grandTotal,
        ]);
    }

    $params = [
        'mode' => 'payment',
        'customer_email' => $request->user()->email,
        'line_items' => $lineItems,
        'shipping_options' => $shippingOptions,
        'client_reference_id' => $order->order_number,
        'metadata' => [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ],
        'payment_intent_data' => [
            'description' => 'Order #' . $order->order_number,
            'metadata' => ['order_id' => $order->id],
        ],
        'success_url' => env('FRONTEND_URL') . '/order-confirmation/' . $order->id,
        'cancel_url' => env('FRONTEND_URL') . '/checkout?payment=cancelled',
    ];

    if ($discounts) {
        $params['discounts'] = $discounts;
    }

    $session = Session::create($params);

    $order->update([
        'payment_status' => 'PENDING',
        'payment_method' => 'STRIPE',
        'stripe_session_id' => $session->id,
    ]);

    return response()->json(['payment_url' => $session->url]);
}

    public function webhook(Request $request)
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $order = Order::find($session->metadata->order_id);

            if ($order) {
                $order->update([
                    'payment_status' => 'PAID',
                    'order_status' => 'PROCESSING',
                    'payment_method' => 'STRIPE',
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'payment_reference' => $session->payment_intent,
                    'paid_at' => now(),
                ]);

                CartItem::where('user_id', $order->user_id)->delete();
            }
        }

        return response()->json(['received' => true]);
    }
}