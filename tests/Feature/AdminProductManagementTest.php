<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_duplicate_a_product_with_independent_images(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('ADMIN', 'admin@example.com'));

        $source = $this->product('Original Product', 'ORIGINAL', 'ACTIVE');
        Storage::disk('public')->put('products/original.jpg', 'image contents');
        $source->images()->create([
            'image_path' => 'products/original.jpg',
            'alt_text' => 'Original image',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $this->postJson("/api/admin/products/{$source->id}/duplicate")
            ->assertSuccessful()
            ->assertJsonPath('data.title', 'Original Product (Copy)')
            ->assertJsonPath('data.sku', 'ORIGINAL-COPY')
            ->assertJsonPath('data.status', 'ACTIVE');

        $duplicate = Product::where('sku', 'ORIGINAL-COPY')->firstOrFail();
        $duplicateImage = $duplicate->images()->firstOrFail();

        $this->assertNotSame($source->slug, $duplicate->slug);
        $this->assertSame($source->category_id, $duplicate->category_id);
        $this->assertSame($source->stock, $duplicate->stock);
        $this->assertNotSame('products/original.jpg', $duplicateImage->image_path);
        $this->assertSame(1, $duplicateImage->is_primary);
        Storage::disk('public')->assertExists('products/original.jpg');
        Storage::disk('public')->assertExists($duplicateImage->image_path);
        $this->assertDatabaseCount('inventory_transactions', 0);

        $this->postJson("/api/admin/products/{$source->id}/duplicate")
            ->assertSuccessful()
            ->assertJsonPath('data.title', 'Original Product (Copy 2)')
            ->assertJsonPath('data.sku', 'ORIGINAL-COPY-2');
    }

    public function test_admin_can_bulk_duplicate_products(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('ADMIN', 'bulk@example.com'));
        $first = $this->product('First Product', 'FIRST', 'ACTIVE');
        $second = $this->product('Second Product', 'SECOND', 'ACTIVE');

        $this->postJson('/api/admin/products/bulk-duplicate', [
            'product_ids' => [$first->id, $second->id],
        ])->assertSuccessful()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('products', ['sku' => 'FIRST-COPY']);
        $this->assertDatabaseHas('products', ['sku' => 'SECOND-COPY']);
        $this->assertDatabaseCount('products', 4);
    }

    public function test_admin_can_bulk_update_products_with_the_same_status(): void
    {
        Sanctum::actingAs($this->user('ADMIN', 'status@example.com'));
        $first = $this->product('Active One', 'ACTIVE-ONE', 'ACTIVE');
        $second = $this->product('Active Two', 'ACTIVE-TWO', 'ACTIVE');

        $this->patchJson('/api/admin/products/bulk-status', [
            'product_ids' => [$first->id, $second->id],
            'status' => 'INACTIVE',
        ])->assertSuccessful()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('products', ['id' => $first->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('products', ['id' => $second->id, 'status' => 'INACTIVE']);
    }

    public function test_bulk_status_rejects_mixed_source_statuses(): void
    {
        Sanctum::actingAs($this->user('ADMIN', 'mixed@example.com'));
        $active = $this->product('Active Product', 'MIX-ACTIVE', 'ACTIVE');
        $inactive = $this->product('Inactive Product', 'MIX-INACTIVE', 'INACTIVE');

        $this->patchJson('/api/admin/products/bulk-status', [
            'product_ids' => [$active->id, $inactive->id],
            'status' => 'INACTIVE',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('product_ids');

        $this->assertDatabaseHas('products', ['id' => $active->id, 'status' => 'ACTIVE']);
        $this->assertDatabaseHas('products', ['id' => $inactive->id, 'status' => 'INACTIVE']);
    }

    public function test_customer_cannot_use_product_duplication_or_bulk_status_endpoints(): void
    {
        Sanctum::actingAs($this->user('CUSTOMER', 'customer-product@example.com'));
        $product = $this->product('Protected Product', 'PROTECTED', 'ACTIVE');

        $this->postJson("/api/admin/products/{$product->id}/duplicate")->assertForbidden();
        $this->postJson('/api/admin/products/bulk-duplicate', [
            'product_ids' => [$product->id],
        ])->assertForbidden();
        $this->patchJson('/api/admin/products/bulk-status', [
            'product_ids' => [$product->id],
            'status' => 'INACTIVE',
        ])->assertForbidden();
    }

    private function user(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'name' => $roleName,
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'password' => Hash::make('Password!123'),
        ]);
    }

    private function product(string $title, string $sku, string $status): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'product-management'],
            ['name' => 'Product Management', 'is_active' => true]
        );

        return Product::create([
            'category_id' => $category->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'sku' => $sku,
            'description' => 'Test product',
            'price' => 25,
            'stock' => 7,
            'status' => $status,
        ]);
    }
}
