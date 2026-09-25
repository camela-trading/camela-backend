<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\User;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HitPayService
{
    public function createPayment(User $user)
    {
        $order = Order::where('user_id', $user->id)
            ->whereIn('payment_status', ['UNPAID', 'PENDING'])
            ->latest()
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'No unpaid order found.',
            ], 404);
        }

        $apiKey = config('services.hitpay.api_key');
        $baseUrl = rtrim((string) config('services.hitpay.base_url', ''), '/');
        $currency = config('services.hitpay.currency', 'PHP');
        $paymentMethods = array_values(array_filter((array) config('services.hitpay.payment_methods', [])));
        $successUrl = str_replace('{order_id}', (string) $order->id, (string) config('services.hitpay.success_url'));

        if (empty($apiKey) || $baseUrl === '') {
            return response()->json([
                'message' => 'HitPay is not configured.',
                'errors' => [
                    'api_key' => empty($apiKey) ? ['Missing HitPay API key.'] : [],
                    'base_url' => $baseUrl === '' ? ['Missing HitPay base URL.'] : [],
                ],
            ], 422);
        }

        $payload = [
            'amount' => number_format((float) $order->grand_total, 2, '.', ''),
            'currency' => $currency,
            'email' => $user->email,
            'name' => $user->name,
            'purpose' => $order->order_number,
            'reference_number' => $order->order_number,
            'redirect_url' => $successUrl,
        ];

        if (!empty($paymentMethods)) {
            $payload['payment_methods'] = $paymentMethods;
        }

        $response = Http::withHeaders([
            'X-BUSINESS-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->post($baseUrl . '/v1/payment-requests', $payload);

        if (!$response->successful()) {
            Log::warning('HitPay payment request failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'response' => $response->json(),
                'payment_methods' => $paymentMethods,
            ]);

            return response()->json([
                'message' => 'Unable to create payment.',
                'error' => $response->json(),
            ], $response->status() ?: 500);
        }

        $body = $response->json() ?? [];
        $paymentRequestId = $body['id'] ?? $body['payment_request_id'] ?? null;
        $paymentUrl = $body['url'] ?? $body['payment_url'] ?? null;

        $order->update([
            'payment_status' => 'PENDING',
            'payment_method' => 'HITPAY',
            'payment_request_id' => $paymentRequestId,
            'hitpay_reference' => $paymentRequestId,
            'gateway_response' => $body,
            'payment_reference' => $paymentRequestId,
        ]);

        return response()->json([
            'payment_url' => $paymentUrl,
            'payment_request_id' => $paymentRequestId,
            'order_number' => $order->order_number,
            'amount' => (string) $order->grand_total,
            'currency' => $currency,
            'payment_methods' => $paymentMethods,
            'payment_status' => 'pending',
        ]);
    }

    public function handleWebhook(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 403);
        }

        $payload = $request->all();
        $paymentRequestId = $payload['reference_number'] ?? $payload['reference'] ?? null;

        if (!$paymentRequestId) {
            return response()->json([
                'message' => 'Missing payment reference.',
            ], 400);
        }

        $order = Order::where('order_number', $paymentRequestId)
            ->orWhere('payment_request_id', $paymentRequestId)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        DB::transaction(function () use ($order, $payload, $paymentRequestId) {
            $status = strtolower((string) ($payload['status'] ?? ''));
            $paymentId = $payload['payment_id'] ?? $payload['transaction_id'] ?? $payload['id'] ?? null;
            $responseBody = $payload;

            $baseUpdate = [
                'payment_request_id' => $paymentRequestId,
                'hitpay_reference' => $paymentRequestId,
                'transaction_reference' => $paymentId,
                'callback_response' => $responseBody,
                'payment_reference' => $paymentId ?: $paymentRequestId,
                'payment_method' => 'HITPAY',
            ];

            switch ($status) {
                case 'completed':
                case 'success':
                    $order->update($baseUpdate + [
                        'payment_status' => 'PAID',
                        'order_status' => 'PROCESSING',
                        'paid_at' => now(),
                    ]);
                    CartItem::where('user_id', $order->user_id)->delete();
                    break;

                case 'failed':
                    $order->update($baseUpdate + [
                        'payment_status' => 'FAILED',
                    ]);
                    break;

                case 'cancelled':
                case 'canceled':
                    $order->update($baseUpdate + [
                        'payment_status' => 'CANCELLED',
                    ]);
                    break;

                case 'expired':
                    $order->update($baseUpdate + [
                        'payment_status' => 'EXPIRED',
                    ]);
                    break;

                case 'pending':
                    $order->update($baseUpdate + [
                        'payment_status' => 'PENDING',
                    ]);
                    break;

                default:
                    $order->update($baseUpdate + [
                        'gateway_response' => $responseBody,
                    ]);
                    break;
            }
        });

        return response()->json([
            'success' => true,
        ]);
    }

    public function handleCallback(Request $request)
    {
        $paymentRequestId = $request->get('reference')
            ?? $request->get('reference_number')
            ?? $request->get('payment_request_id');

        if (!$paymentRequestId) {
            return response()->json([
                'message' => 'Missing payment reference.',
            ], 400);
        }

        $order = Order::where('payment_request_id', $paymentRequestId)
            ->orWhere('order_number', $paymentRequestId)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        $statusFromQuery = strtolower((string) ($request->get('status') ?? ''));
        $paymentData = $this->fetchPaymentRequest($paymentRequestId);

        if ($paymentData) {
            $this->applyGatewayState($order, $paymentData);
        }

        if (in_array($order->payment_status, ['PAID'], true) || in_array($statusFromQuery, ['completed', 'success', 'paid'], true)) {
            return redirect()->away($this->frontendUrl() . '/order-confirmation/' . $order->id);
        }

        if (in_array($statusFromQuery, ['failed', 'expired'], true) || in_array($order->payment_status, ['FAILED', 'EXPIRED'], true)) {
            return redirect()->away($this->frontendUrl() . '/checkout?payment=' . strtolower((string) $order->payment_status));
        }

        if (in_array($statusFromQuery, ['cancelled', 'canceled'], true) || in_array($order->payment_status, ['CANCELLED'], true)) {
            return redirect()->away($this->frontendUrl() . '/checkout?payment=cancelled');
        }

        return redirect()->away($this->frontendUrl() . '/checkout?payment=pending');
    }

    private function fetchPaymentRequest(string $paymentRequestId): ?array
    {
        $apiKey = config('services.hitpay.api_key');
        $baseUrl = rtrim((string) config('services.hitpay.base_url', ''), '/');

        if (empty($apiKey) || $baseUrl === '') {
            return null;
        }

        $response = Http::withHeaders([
            'X-BUSINESS-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->get($baseUrl . '/v1/payment-requests/' . $paymentRequestId);

        if (!$response->successful()) {
            return null;
        }

        return $response->json() ?: null;
    }

    private function applyGatewayState(Order $order, array $paymentData): void
    {
        $status = strtolower((string) ($paymentData['status'] ?? ''));
        $paymentId = $paymentData['payment_id'] ?? $paymentData['transaction_id'] ?? $paymentData['id'] ?? null;

        $update = [
            'gateway_response' => $paymentData,
            'payment_request_id' => $paymentData['id'] ?? $order->payment_request_id,
            'hitpay_reference' => $paymentData['reference_number'] ?? $order->hitpay_reference,
            'transaction_reference' => $paymentId,
            'payment_reference' => $paymentId ?: $order->payment_reference,
            'payment_method' => 'HITPAY',
        ];

        if (in_array($status, ['completed', 'success', 'paid'], true)) {
            $update['payment_status'] = 'PAID';
            $update['order_status'] = 'PROCESSING';
            $update['paid_at'] = $order->paid_at ?: now();
            CartItem::where('user_id', $order->user_id)->delete();
        } elseif (in_array($status, ['failed'], true)) {
            $update['payment_status'] = 'FAILED';
        } elseif (in_array($status, ['cancelled', 'canceled'], true)) {
            $update['payment_status'] = 'CANCELLED';
        } elseif (in_array($status, ['expired'], true)) {
            $update['payment_status'] = 'EXPIRED';
        } elseif (in_array($status, ['pending'], true)) {
            $update['payment_status'] = 'PENDING';
        }

        $order->update($update);
    }

    private function frontendUrl(): string
    {
        return rtrim((string) config('services.hitpay.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
    }

    private function verifyWebhook(Request $request): bool
    {
        $salt = config('services.hitpay.salt');

        if (!$salt) {
            return false;
        }

        $signature = $request->header('Hitpay-Signature') ?? $request->header('Hmac') ?? $request->header('X-HitPay-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $salt);

        return hash_equals($expected, $signature);
    }
}
