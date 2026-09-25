<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * View Cart
     */
    public function index(Request $request)
    {
        $items = $request->user()
            ->cartItems()
            ->with('product.images')
            ->get();

        return new CartResource($items);
    }

    /**
     * Add To Cart
     */
    public function store(AddToCartRequest $request)
    {
        $product = Product::findOrFail(
            $request->product_id
        );

        $item = $this->cartService->add(

            $request->user(),

            $product,

            $request->quantity

        );

        return new CartItemResource(

            $item->load('product.images')

        );
    }

    /**
     * Update Quantity
     */
    public function update(
        UpdateCartRequest $request,
        CartItem $cartItem
    )
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);

        if ($request->quantity > $cartItem->product->stock) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
            ], 422);
        }

        $item = $this->cartService->update(

            $cartItem,

            $request->quantity

        );

        return new CartItemResource(

            $item->load('product.images')

        );
    }

    /**
     * Remove Item
     */
    public function destroy(
        Request $request,
        CartItem $cartItem
    )
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);

        $this->cartService
            ->remove($cartItem);

        return response()->json([

            'success' => true,

            'message' => 'Item removed from cart.'

        ]);
    }

    /**
     * Clear Cart
     */
    public function clear(Request $request)
    {
        $this->cartService->clear(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared.'
        ]);
    }
}
