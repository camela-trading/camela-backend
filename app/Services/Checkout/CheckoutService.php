<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use App\Services\Inventory\InventoryService;
use App\Services\Cart\CartService;
use App\Services\Notifications\AdminNotificationService;
use App\Models\OrderItem;
use App\Models\StoreSetting;

class CheckoutService
{
    public function __construct(

        private InventoryService $inventoryService,

        private CartService $cartService,

        private AdminNotificationService $notificationService

    ) {}

    public function checkout(User $user, string $paymentMethod, ?int $shippingAddressId = null, ?int $billingAddressId = null, string $shippingMethod = 'standard'): Order
    {
        return DB::transaction(function () use ($user, $paymentMethod, $shippingAddressId, $billingAddressId, $shippingMethod) {

            // Load customer's cart
            $items = $user
                ->cartItems()
                ->with('product')
                ->get();

            if ($items->isEmpty()) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'Cart is empty.',
                    ], 422)
                );

            }

            // Verify stock availability
            foreach ($items as $item) {

                if ($item->product->stock < $item->quantity) {

                    throw new HttpResponseException(
                        response()->json([
                            'message' => "{$item->product->title} has insufficient stock.",
                        ], 422)
                    );

                }

            }

            $subtotal = $items->sum(function ($item) {

                return $item->quantity * $item->product->price;

            });

            $totalQuantity = (int) $items->sum('quantity');

            $settings = StoreSetting::firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            switch ($shippingMethod) {

                case 'express':
                    $shippingRate = $settings->express_shipping;
                    break;

                case 'overnight':
                    $shippingRate = $settings->overnight_shipping;
                    break;

                default:
                    $shippingRate = $settings->standard_shipping;
            }

            $shipping = round((float) $shippingRate * $totalQuantity, 2);
            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */

            $discount = 0;

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            $tax = ($subtotal * (float) $settings->tax_rate) / 100;

            $grandTotal =

                $subtotal

                + $shipping

                + $tax

                - $discount;

            $order = Order::create([

                'user_id' => $user->id,

                'order_number' => $this->generateOrderNumber(),

                'subtotal' => $subtotal,

                'shipping_fee' => $shipping,

                'discount' => $discount,

                'tax' => $tax,

                'grand_total' => $grandTotal,

                'payment_status' => 'UNPAID',

                'order_status' => 'PENDING',

                'payment_method' => $paymentMethod,
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,

            ]);

            foreach ($items as $item) {

                OrderItem::create([

                    'order_id' => $order->id,

                    'product_id' => $item->product_id,

                    'quantity' => $item->quantity,

                    'price' => $item->product->price,

                    'subtotal' =>

                        $item->quantity

                        * $item->product->price,

                ]);

                $this->inventoryService->adjustStock(

                    $item->product,

                    'STOCK_OUT',

                    $item->quantity,

                    $user,

                    "Order {$order->order_number}"

                );

            }

            $order = $order->load([

                'items.product.images',

                'user',

            ]);

            $this->notificationService->notify(
                'notify_new_order',
                'new_order',
                'New Order Placed',
                "Order {$order->order_number} has been placed by {$user->name}.",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $user->id,
                    'grand_total' => $order->grand_total,
                ],
                '/admin/orders'
            );

            // Clear cart after successful checkout
            $this->cartService->clear($user);

            return $order;

        });
    }

    private function generateOrderNumber(): string
    {
        return

            'CAM-'

            . now()->format('YmdHis')

            . '-'

            . random_int(1000, 9999);
    }
}
