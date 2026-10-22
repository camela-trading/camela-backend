<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JntWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('JNT webhook', ['headers' => $request->headers->all(), 'body' => $request->all()]);

        // Signature = HMAC-SHA1 of X-SimplyPost-Id, keyed with your secret
        $secret = config('services.jnt.webhook_secret');
        if ($secret) {
            $expected = hash_hmac('sha1', (string) $request->header('X-SimplyPost-Id'), $secret);
            if (! hash_equals($expected, (string) $request->header('X-SimplyPost-Signature'))) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $status = $request->input('status') ?? $request->header('X-SimplyPost-Event');

        $order = Order::where('jnt_tracking_id', $request->input('tracking_id'))
            ->orWhere('order_number', $request->input('reference_number'))
            ->first();

        if ($order && $status) {
            $order->update([
                'jnt_status'            => $status,
                'jnt_status_updated_at' => now(),
            ]);
        }

        return response()->json(['received' => true]);
    }
}