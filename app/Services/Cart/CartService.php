<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;

class CartService
{
    /**
     * Add product to cart.
     */
    public function add(
        User $user,
        Product $product,
        int $quantity
    ): CartItem
    {
        return DB::transaction(function () use (
            $user,
            $product,
            $quantity
        ) {

            $item = CartItem::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            if ($item) {

                if ($item->quantity + $quantity > $product->stock) {
                    throw new HttpResponseException(
                        response()->json([
                            'message' => 'Requested quantity exceeds available stock.',
                        ], 422)
                    );
                }

                $item->increment('quantity', $quantity);

                return $item->fresh();

            }

            if ($quantity > $product->stock) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'Requested quantity exceeds available stock.',
                    ], 422)
                );
            }

            return CartItem::create([

                'user_id' => $user->id,

                'product_id' => $product->id,

                'quantity' => $quantity,

            ]);

        });
    }

    /**
     * Update quantity.
     */
    public function update(
        CartItem $item,
        int $quantity
    ): CartItem
    {
        $item->update([

            'quantity' => $quantity

        ]);

        return $item->fresh();
    }

    /**
     * Remove item.
     */
    public function remove(
        CartItem $item
    ): void
    {
        $item->delete();
    }

    /**
     * Clear entire cart.
     */
    public function clear(
        User $user
    ): void
    {
        $user
            ->cartItems()
            ->delete();
    }

    /**
     * Cart subtotal.
     */
    public function subtotal(
        User $user
    ): float
    {
        return $user
            ->cartItems
            ->sum(function ($item) {

                return $item->product->price * $item->quantity;

            });
    }
}
