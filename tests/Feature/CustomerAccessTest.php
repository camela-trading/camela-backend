<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_customer_endpoints_are_protected(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/checkout', [])->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_unverified_customer_can_add_to_cart_and_checkout(): void
    {
        $customer = $this->customer('unverified@example.com');
        $product = $this->product(stock: 5);

        StoreSetting::create([
            'store_name' => 'Camela',
            'support_email' => 'support@example.com',
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertSuccessful();

        $this->postJson('/api/checkout', [
            'payment_method' => 'COD',
            'shipping_method' => 'standard',
        ])->assertCreated()
            ->assertJsonMissing(['message' => 'Email Verification Required']);
    }

    public function test_unverified_customer_can_view_update_and_remove_cart_items(): void
    {
        $customer = $this->customer('unverified-cart@example.com');
        $product = $this->product(stock: 5);
        Sanctum::actingAs($customer);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertSuccessful();

        $item = CartItem::where('user_id', $customer->id)->firstOrFail();
        $this->getJson('/api/cart')->assertSuccessful();
        $this->patchJson("/api/cart/{$item->id}", ['quantity' => 3])
            ->assertSuccessful();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);

        $this->deleteJson("/api/cart/{$item->id}")->assertSuccessful();
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_verified_customer_retains_normal_cart_access(): void
    {
        $customer = $this->customer('verified@example.com');
        $customer->forceFill(['email_verified_at' => now()])->save();
        Sanctum::actingAs($customer);

        $this->postJson('/api/cart', [
            'product_id' => $this->product()->id,
            'quantity' => 1,
        ])->assertSuccessful();

        $this->assertDatabaseHas('cart_items', ['user_id' => $customer->id]);
    }

    public function test_verification_notification_and_signed_link_still_work(): void
    {
        Notification::fake();
        $customer = $this->customer('verification-still-works@example.com');
        Sanctum::actingAs($customer);

        $this->postJson('/api/email/verification/resend', [
            'email' => $customer->email,
        ])->assertSuccessful();
        Notification::assertSentTo($customer, \App\Notifications\VerifyEmailNotification::class);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $customer->id, 'hash' => sha1($customer->email)]
        );
        $this->get($verificationUrl)->assertRedirect();
        $this->assertNotNull($customer->fresh()->email_verified_at);
    }

    public function test_customer_cannot_modify_another_customers_cart_item(): void
    {
        $owner = $this->customer('owner@example.com');
        $attacker = $this->customer('attacker@example.com');
        $item = CartItem::create([
            'user_id' => $owner->id,
            'product_id' => $this->product()->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($attacker);

        $this->patchJson("/api/cart/{$item->id}", ['quantity' => 2])->assertNotFound();
        $this->deleteJson("/api/cart/{$item->id}")->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_cart_rejects_quantities_above_available_stock(): void
    {
        $customer = $this->customer('stock@example.com');
        $product = $this->product(stock: 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertUnprocessable();

        $item = CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->patchJson("/api/cart/{$item->id}", ['quantity' => 3])
            ->assertUnprocessable();
    }

    public function test_customer_cannot_access_admin_endpoints(): void
    {
        Sanctum::actingAs($this->customer('customer@example.com'));

        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }

    private function customer(string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'CUSTOMER']);

        return User::create([
            'role_id' => $role->id,
            'name' => 'Customer',
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'password' => Hash::make('Password!123'),
        ]);
    }

    private function product(int $stock = 10): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'wellness'],
            ['name' => 'Wellness', 'is_active' => true]
        );

        return Product::create([
            'category_id' => $category->id,
            'title' => 'Test Product ' . Product::count(),
            'slug' => 'test-product-' . Product::count(),
            'sku' => 'TEST-' . Product::count(),
            'description' => 'Test product',
            'price' => 10,
            'stock' => $stock,
            'status' => 'ACTIVE',
        ]);
    }
}
