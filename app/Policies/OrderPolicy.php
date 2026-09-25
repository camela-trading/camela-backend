<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Customer may only view his own order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}