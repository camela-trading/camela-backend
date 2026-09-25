<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AbandonedCartReminderNotification;
use App\Notifications\UnverifiedAccountReminderNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class CustomerReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-12 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_recent_unverified_customer_receives_no_reminder(): void
    {
        Notification::fake();
        $user = $this->customer(verified: false, createdAt: now()->subDays(3)->addMinute());

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($user->fresh()->verification_reminder_sent_at);
    }

    public function test_three_day_unverified_customer_receives_one_reminder_only(): void
    {
        Notification::fake();
        $user = $this->customer(verified: false, createdAt: now()->subDays(3));

        $this->artisan('customer-reminders:send')->assertSuccessful();
        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($user, UnverifiedAccountReminderNotification::class, 1);
        $this->assertNotNull($user->fresh()->verification_reminder_sent_at);
    }

    public function test_verified_customer_receives_no_verification_reminder(): void
    {
        Notification::fake();
        $this->customer(verified: true, createdAt: now()->subDays(4));

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_inactive_customer_receives_no_reminders(): void
    {
        Notification::fake();
        $user = $this->customer(verified: false, createdAt: now()->subDays(4));
        $this->cartItem($user, now()->subDays(4));
        $user->forceFill(['is_active' => false])->save();

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_previously_reminded_unverified_customer_receives_no_duplicate(): void
    {
        Notification::fake();
        $user = $this->customer(verified: false, createdAt: now()->subDays(4));
        $user->forceFill(['verification_reminder_sent_at' => now()->subDay()])->save();

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_failed_verification_email_is_not_marked_sent_and_other_customers_continue(): void
    {
        $failed = $this->customer(verified: false, createdAt: now()->subDays(4), email: 'fail@example.com');
        $successful = $this->customer(verified: false, createdAt: now()->subDays(4), email: 'success@example.com');
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->twice()->andReturnUsing(function ($notifiable): void {
            if ($notifiable->email === 'fail@example.com') {
                throw new \RuntimeException('Simulated mail transport failure.');
            }
        });
        $this->app->instance(Dispatcher::class, $dispatcher);

        $this->artisan('customer-reminders:send')->assertSuccessful();

        $this->assertNull($failed->fresh()->verification_reminder_sent_at);
        $this->assertNotNull($successful->fresh()->verification_reminder_sent_at);
    }

    public function test_recent_cart_receives_no_reminder(): void
    {
        Notification::fake();
        $user = $this->customer();
        $this->cartItem($user, now()->subDays(2));

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_three_day_idle_cart_receives_one_reminder(): void
    {
        Notification::fake();
        $user = $this->customer();
        $this->cartItem($user, now()->subDays(3));

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($user, AbandonedCartReminderNotification::class, 1);
        $this->assertNotNull($user->fresh()->abandoned_cart_reminder_activity_at);
    }

    public function test_empty_or_cleared_cart_receives_no_reminder(): void
    {
        Notification::fake();
        $emptyUser = $this->customer(email: 'empty@example.com');
        $clearedUser = $this->customer(email: 'cleared@example.com');
        $this->cartItem($clearedUser, now()->subDays(4))->delete();

        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('cart_items', ['user_id' => $clearedUser->id]);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $emptyUser->id]);
    }

    public function test_unchanged_cart_receives_no_duplicate_reminder(): void
    {
        Notification::fake();
        $user = $this->customer();
        $this->cartItem($user, now()->subDays(4));

        $this->artisan('customer-reminders:send')->assertSuccessful();
        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($user, AbandonedCartReminderNotification::class, 1);
    }

    public function test_cart_modification_resets_timer_and_allows_a_later_reminder(): void
    {
        Notification::fake();
        $user = $this->customer();
        $item = $this->cartItem($user, now()->subDays(4));
        $this->artisan('customer-reminders:send')->assertSuccessful();

        Carbon::setTestNow(now()->addDay());
        $item->update(['quantity' => 2]);
        $this->artisan('customer-reminders:send')->assertSuccessful();
        Notification::assertSentToTimes($user, AbandonedCartReminderNotification::class, 1);

        Carbon::setTestNow(now()->addDays(3));
        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($user, AbandonedCartReminderNotification::class, 2);
    }

    public function test_removing_an_item_resets_timer_when_other_items_remain(): void
    {
        Notification::fake();
        $user = $this->customer();
        $removedItem = $this->cartItem($user, now()->subDays(4));
        $this->cartItem($user, now()->subDays(4));

        $removedItem->delete();
        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertNothingSent();

        Carbon::setTestNow(now()->addDays(3));
        $this->artisan('customer-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($user, AbandonedCartReminderNotification::class, 1);
    }

    public function test_one_failed_cart_email_does_not_stop_other_customers(): void
    {
        $failed = $this->customer(email: 'fail-cart@example.com');
        $successful = $this->customer(email: 'success-cart@example.com');
        $this->cartItem($failed, now()->subDays(4));
        $this->cartItem($successful, now()->subDays(4));
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->twice()->andReturnUsing(function ($notifiable): void {
            if ($notifiable->email === 'fail-cart@example.com') {
                throw new \RuntimeException('Simulated mail transport failure.');
            }
        });
        $this->app->instance(Dispatcher::class, $dispatcher);

        $this->artisan('customer-reminders:send')->assertSuccessful();

        $this->assertNull($failed->fresh()->abandoned_cart_reminder_activity_at);
        $this->assertNotNull($successful->fresh()->abandoned_cart_reminder_activity_at);
    }

    public function test_dry_run_counts_eligibility_without_sending_or_saving(): void
    {
        Notification::fake();
        $user = $this->customer(verified: false, createdAt: now()->subDays(4));
        $this->cartItem($user, now()->subDays(4));

        $this->artisan('customer-reminders:send --dry-run')
            ->expectsOutput('Verification reminders eligible: 1')
            ->expectsOutput('Abandoned carts eligible: 1')
            ->assertSuccessful();

        Notification::assertNothingSent();
        $user->refresh();
        $this->assertNull($user->verification_reminder_sent_at);
        $this->assertNull($user->abandoned_cart_reminder_activity_at);
    }

    public function test_email_content_uses_secure_existing_links_and_no_prices_or_credentials(): void
    {
        config(['services.frontend_url' => 'https://shop.example.com']);
        $user = $this->customer(verified: false, createdAt: now()->subDays(4));

        $verificationMail = (new UnverifiedAccountReminderNotification)->toMail($user);
        $verificationRequest = Request::create($verificationMail->actionUrl);
        $cartMail = (new AbandonedCartReminderNotification(2))->toMail($user);

        $this->assertTrue(URL::hasValidSignature($verificationRequest));
        $this->assertSame('https://shop.example.com/cart', $cartMail->actionUrl);
        $this->assertStringContainsString($user->name, $verificationMail->greeting);
        $this->assertStringContainsString($user->name, $cartMail->greeting);
        $this->assertStringNotContainsString('password', strtolower(implode(' ', $verificationMail->introLines)));
        $this->assertStringNotContainsString('price', strtolower(implode(' ', $cartMail->introLines)));
        $this->assertStringContainsString('pricing is confirmed at checkout', implode(' ', $cartMail->outroLines));
    }

    private function customer(
        bool $verified = true,
        ?Carbon $createdAt = null,
        ?string $email = null
    ): User {
        $role = Role::firstOrCreate(['name' => 'CUSTOMER']);
        $user = User::create([
            'role_id' => $role->id,
            'name' => 'Reminder Customer',
            'username' => 'customer-'.str()->random(8),
            'email' => $email ?? fake()->unique()->safeEmail(),
            'password' => Hash::make('Password!123'),
        ]);

        $user->forceFill([
            'email_verified_at' => $verified ? now() : null,
            'created_at' => $createdAt ?? now(),
        ])->saveQuietly();

        return $user->fresh();
    }

    private function cartItem(User $user, Carbon $activityAt): CartItem
    {
        $category = Category::firstOrCreate(
            ['slug' => 'reminders'],
            ['name' => 'Reminders', 'is_active' => true]
        );
        $number = Product::count() + 1;
        $product = Product::create([
            'category_id' => $category->id,
            'title' => "Reminder Product {$number}",
            'slug' => "reminder-product-{$number}",
            'sku' => "REMINDER-{$number}",
            'description' => 'Reminder test product',
            'price' => 10,
            'stock' => 10,
            'status' => 'ACTIVE',
        ]);
        $item = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        DB::table('cart_items')->where('id', $item->id)->update([
            'created_at' => $activityAt,
            'updated_at' => $activityAt,
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'cart_activity_at' => $activityAt,
        ]);

        return $item->fresh();
    }
}
