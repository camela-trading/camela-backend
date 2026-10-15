<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Coupon;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentController extends Controller
{
    public function create(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->with('items.product')
            ->firstOrFail();

        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = [];

        // One line per product in the order
        foreach ($order->items as $item) {
            $lineItems[] = [
                'quantity' => (int) $item->quantity,
                'price_data' => [
                    'currency' => 'sgd',
                    'unit_amount' => (int) round($item->price * 100),
                    'product_data' => [
                        // Change "name" if your products table uses a different column
                        'name' => $item->product->name ?? 'Item',
                    ],
                ],
            ];
        }

        // Shipping
        if ($order->shipping_fee > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'sgd',
                    'unit_amount' => (int) round($order->shipping_fee * 100),
                    'product_data' => ['name' => 'Shipping'],
                ],
            ];
        }

        // Tax
        if ($order->tax > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'sgd',
                    'unit_amount' => (int) round($order->tax * 100),
                    'product_data' => ['name' => 'Tax'],
                ],
            ];
        }

        $sessionData = [
            'mode' => 'payment',
            'customer_email' => $request->user()->email,
            'line_items' => $lineItems,
            'payment_intent_data' => [
                'description' => 'Order #' . $order->order_number,
            ],
            'metadata' => ['order_id' => $order->id],
            'success_url' => env('FRONTEND_URL') . '/order-confirmation/' . $order->id,
            'cancel_url' => env('FRONTEND_URL') . '/checkout?payment=cancelled',
        ];

        // Discount (Stripe doesn't allow negative line items, so use a one-time coupon)
        if ($order->discount > 0) {
            $coupon = Coupon::create([
                'amount_off' => (int) round($order->discount * 100),
                'currency' => 'sgd',
                'duration' => 'once',
                'name' => 'Discount',
            ]);

            $sessionData['discounts'] = [['coupon' => $coupon->id]];
        }

        $session = Session::create($sessionData);

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