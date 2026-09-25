<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;

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

    /**
     * Update order status
     */
    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    )
    {
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
