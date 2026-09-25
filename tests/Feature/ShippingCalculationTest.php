<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StoreSetting::create([
            'store_name' => 'Camela Group',
            'support_email' => 'support@example.com',
            'standard_shipping' => 8,
            'express_shipping' => 15,
            'overnight_shipping' => 25,
            'free_shipping_threshold' => 300,
            'tax_rate' => 0,
            'notify_new_order' => false,
        ]);
    }

    public function test_standard_shipping_is_the_default_and_uses_admin_rate(): void
    {
        $user = $this->customer();
        $this->addCartItem($user, 1);

        $order = $this->checkout($user);

        $this->assertSame('8.00', $order->shipping_fee);
    }

    public function test_standard_shipping_multiplies_rate_by_total_quantity(): void
    {
        $user = $this->customer();
        $this->addCartItem($user, 3);

        $order = $this->checkout($user, ['shipping_fee' => 1]);

        $this->assertSame('24.00', $order->shipping_fee);
        $this->assertSame('54.00', $order->grand_total);
    }

    public function test_shipping_uses_sum_of_quantities_across_cart_lines(): void
    {
        $user = $this->customer();
        $this->addCartItem($user, 2);
        $this->addCartItem($user, 3);

        $order = $this->checkout($user);

        $this->assertSame('40.00', $order->shipping_fee);
    }

    public function test_express_shipping_uses_its_admin_rate_per_quantity(): void
    {
        $user = $this->customer();
        $this->addCartItem($user, 3);

        $order = $this->checkout($user, ['shipping_method' => 'express']);

        $this->assertSame('45.00', $order->shipping_fee);
    }

    public function test_changed_quantity_is_used_for_shipping(): void
    {
        $user = $this->customer();
        $item = $this->addCartItem($user, 2);
        $item->update(['quantity' => 5]);

        $order = $this->checkout($user);

        $this->assertSame('40.00', $order->shipping_fee);
    }

    public function test_empty_cart_cannot_create_shipping_or_order(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', ['payment_method' => 'COD'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_standard_shipping_does_not_become_free_at_the_legacy_threshold(): void
    {
        StoreSetting::query()->update(['free_shipping_threshold' => 20]);
        $user = $this->customer();
        $this->addCartItem($user, 3);

        $order = $this->checkout($user);

        $this->assertSame('24.00', $order->shipping_fee);
    }

    public function test_unknown_shipping_method_is_rejected(): void
    {
        $user = $this->customer();
        $this->addCartItem($user, 1);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'payment_method' => 'COD',
            'shipping_method' => 'free',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_method');

        $this->assertDatabaseCount('orders', 0);
    }

    private function checkout(User $user, array $extra = []): Order
    {
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', array_merge([
            'payment_method' => 'COD',
        ], $extra))->assertCreated();

        return Order::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function customer(): User
    {
        $role = Role::firstOrCreate(['name' => 'CUSTOMER']);
        $number = User::count() + 1;

        return User::create([
            'role_id' => $role->id,
            'name' => 'Shipping Customer',
            'username' => "shipping-customer-{$number}",
            'email' => "shipping-customer-{$number}@example.com",
            'password' => Hash::make('Password!123'),
        ]);
    }

    private function addCartItem(User $user, int $quantity): CartItem
    {
        $category = Category::firstOrCreate(
            ['slug' => 'shipping'],
            ['name' => 'Shipping', 'is_active' => true]
        );
        $number = Product::count() + 1;
        $product = Product::create([
            'category_id' => $category->id,
            'title' => "Shipping Product {$number}",
            'slug' => "shipping-product-{$number}",
            'sku' => "SHIPPING-{$number}",
            'description' => 'Shipping calculation product',
            'price' => 10,
            'stock' => 20,
            'status' => 'ACTIVE',
        ]);

        return CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }
}
