<?php

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Services\Shipping\JntService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * List all orders
     */
    public function index()
    {
        $orders = Order::latest()
            ->with([
                'user',
                'items.product.images',
                'shippingAddress',
                'billingAddress'
            ])
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    /**
     * View single order
     */
    public function show(Order $order)
    {
        return new OrderResource(
            $order->load([
                'user',
                'items.product.images',
                'shippingAddress',
                'billingAddress'
            ])
        );
    }
public function refreshJnt(Order $order, JntService $jnt)
{
    if (!$order->jnt_tracking_id) {
        return response()->json(['message' => 'No J&T shipment for this order yet.'], 422);
    }

    $status = $jnt->latestStatus($order->jnt_tracking_id);

    if ($status) {
        $order->update([
            'jnt_status' => $status,
            'jnt_status_updated_at' => now(),
        ]);
    }

    return response()->json(['success' => true, 'data' => new OrderResource($order->fresh())]);
}
    public function label(Order $order, JntService $jnt)
    {
        if (!$order->jnt_tracking_id) {
            return response()->json([
                'message' => 'No J&T shipment for this order yet.'
            ], 422);
        }

        return response()->json($jnt->label($order->jnt_tracking_id));
    }

    /**
     * Update order status
     */
    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    )
    {
        if (strtoupper($request->order_status) === 'SHIPPED' && !$order->jnt_tracking_id) {
            try {
                $res = app(JntService::class)->createOrder(
                    $order->load(['user', 'shippingAddress'])
                );
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'J&T shipment failed: ' . $e->getMessage(),
                ], 422);
            }

            \Log::info('JNT create response', ['res' => $res]);

            $trackingId = $res['tracking_id'] ?? $res['data']['tracking_id'] ?? null;

            if (!$trackingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'J&T did not return a tracking ID: ' . json_encode($res),
                ], 422);
            }

            $order->update([
                'jnt_tracking_id'       => $trackingId,
                'jnt_sorting_code'      => $res['sorting_code'] ?? $res['data']['sorting_code'] ?? null,
                'jnt_status'            => 'PENDING_PICKUP',
                'jnt_status_updated_at' => now(),
            ]);
        }
        if ($order->jnt_tracking_id && blank($order->jnt_status)) {
            $order->update([
                'jnt_status'            => 'PENDING_PICKUP',
                'jnt_status_updated_at' => now(),
            ]);
        }

        $order = $this->orderService->updateStatus(
            $order,
            $request->order_status
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated.',
            'data' => new OrderResource($order)
        ]);
    }
}
