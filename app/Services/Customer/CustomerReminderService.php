<?php

namespace App\Services\Customer;

use App\Models\User;
use App\Notifications\AbandonedCartReminderNotification;
use App\Notifications\UnverifiedAccountReminderNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CustomerReminderService
{
    private const LOCK_SECONDS = 300;

    public function __construct(private readonly Dispatcher $notifications) {}

    public function send(bool $dryRun = false): array
    {
        $stats = [
            'verification_eligible' => 0,
            'verification_sent' => 0,
            'verification_failed' => 0,
            'cart_eligible' => 0,
            'cart_sent' => 0,
            'cart_failed' => 0,
        ];

        $this->verificationCandidates()->chunkById(100, function ($users) use (&$stats, $dryRun) {
            foreach ($users as $user) {
                $this->processVerificationReminder($user->id, $dryRun, $stats);
            }
        });

        $this->cartCandidates()->chunkById(100, function ($users) use (&$stats, $dryRun) {
            foreach ($users as $user) {
                $this->processCartReminder($user->id, $dryRun, $stats);
            }
        });

        return $stats;
    }

    private function verificationCandidates(): Builder
    {
        return $this->customerQuery()
            ->whereNull('email_verified_at')
            ->whereNull('verification_reminder_sent_at')
            ->where('created_at', '<=', now()->subDays(3));
    }

    private function cartCandidates(): Builder
    {
        $cutoff = now()->subDays(3);

        return $this->customerQuery()
            ->whereHas('cartItems')
            ->where(function (Builder $query) use ($cutoff) {
                $query->whereNull('cart_activity_at')
                    ->orWhere('cart_activity_at', '<=', $cutoff);
            })
            ->whereDoesntHave('cartItems', function (Builder $query) use ($cutoff) {
                $query->where('updated_at', '>', $cutoff);
            });
    }

    private function customerQuery(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->whereHas('role', function (Builder $query) {
                $query->where('name', 'CUSTOMER');
            });
    }

    private function processVerificationReminder(int $userId, bool $dryRun, array &$stats): void
    {
        Cache::lock("customer-reminders:verification:{$userId}", self::LOCK_SECONDS)
            ->get(function () use ($userId, $dryRun, &$stats) {
                $user = User::find($userId);

                if (!$this->verificationEligible($user)) {
                    return;
                }

                $stats['verification_eligible']++;

                if ($dryRun) {
                    return;
                }

                try {
                    $this->notifications->send($user, new UnverifiedAccountReminderNotification);
                    $user->forceFill(['verification_reminder_sent_at' => now()])->save();
                    $stats['verification_sent']++;
                    Log::info('Verification reminder sent.', ['user_id' => $user->id]);
                } catch (\Throwable $exception) {
                    $stats['verification_failed']++;
                    Log::warning('Verification reminder failed.', [
                        'user_id' => $user->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }

    private function processCartReminder(int $userId, bool $dryRun, array &$stats): void
    {
        Cache::lock("customer-reminders:cart:{$userId}", self::LOCK_SECONDS)
            ->get(function () use ($userId, $dryRun, &$stats) {
                $user = User::find($userId);
                $state = $this->eligibleCartState($user);

                if ($state === null) {
                    return;
                }

                $stats['cart_eligible']++;

                if ($dryRun) {
                    return;
                }

                try {
                    $this->notifications->send(
                        $user,
                        new AbandonedCartReminderNotification($state['item_count'])
                    );
                    $user->forceFill([
                        'abandoned_cart_reminder_activity_at' => $state['last_activity'],
                    ])->save();
                    $stats['cart_sent']++;
                    Log::info('Abandoned cart reminder sent.', ['user_id' => $user->id]);
                } catch (\Throwable $exception) {
                    $stats['cart_failed']++;
                    Log::warning('Abandoned cart reminder failed.', [
                        'user_id' => $user->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }

    private function verificationEligible(?User $user): bool
    {
        return $this->eligibleCustomer($user)
            && $user->email_verified_at === null
            && $user->verification_reminder_sent_at === null
            && $user->created_at?->lte(now()->subDays(3));
    }

    private function eligibleCartState(?User $user): ?array
    {
        if (!$this->eligibleCustomer($user)) {
            return null;
        }

        $itemCount = $user->cartItems()->sum('quantity');
        $itemActivity = $user->cartItems()->max('updated_at');

        if ($itemCount < 1 || $itemActivity === null) {
            return null;
        }

        $lastActivity = collect([
            $user->cart_activity_at,
            Carbon::parse($itemActivity),
        ])->filter()->sortByDesc(fn (CarbonInterface $date) => $date->getTimestamp())->first();

        if ($lastActivity->gt(now()->subDays(3))) {
            return null;
        }

        if ($user->abandoned_cart_reminder_activity_at?->gte($lastActivity)) {
            return null;
        }

        return [
            'item_count' => (int) $itemCount,
            'last_activity' => $lastActivity,
        ];
    }

    private function eligibleCustomer(?User $user): bool
    {
        return $user !== null
            && $user->is_active
            && filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false
            && $user->role()->where('name', 'CUSTOMER')->exists();
    }
}
