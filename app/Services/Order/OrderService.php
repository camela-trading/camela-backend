<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Notifications\AdminNotificationService;

class OrderService
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    public function updateStatus(
        Order $order,
        string $status
    ): Order
    {
        $order->update([
            'order_status' => $status,
        ]);

        if (strtoupper($status) === 'DELIVERED') {
            $order->loadMissing('user');

            $this->notificationService->notify(
                'notify_order_delivered',
                'order_delivered',
                'Order Delivered',
                "Order {$order->order_number} has been marked as delivered.",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ],
                '/admin/orders'
            );
        }

        return $order->fresh([
            'user',
            'items.product.images',
        ]);
    }
}
