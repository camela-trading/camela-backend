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

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'mode' => 'payment',
            'customer_email' => $request->user()->email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'sgd', 
                    'unit_amount' => (int) round($order->grand_total * 100),
                    'product_data' => [
                        'name' => 'Order #' . $order->order_number,
                    ],
                ],
            ]],
            'metadata' => ['order_id' => $order->id],
            'success_url' => env('FRONTEND_URL') . '/order-confirmation/' . $order->id,
            'cancel_url' => env('FRONTEND_URL') . '/checkout?payment=cancelled',
        ]);

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