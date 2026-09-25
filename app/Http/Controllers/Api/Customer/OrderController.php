<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * Customer Order History
     */
    public function index(Request $request)
    {
        $orders = $request
            ->user()
            ->orders()
            ->latest()
            ->with([
                'items.product.images',
                'shippingAddress',
                'billingAddress'
            ])
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    /**
     * View Single Order
     */
    public function show(
        Request $request,
        Order $order
    )
    {
        Gate::authorize('view', $order);

        $order->load([

            'items.product.images',

            'user',

            'shippingAddress',

            'billingAddress'

        ]);

        return new OrderResource($order);
    }
}
