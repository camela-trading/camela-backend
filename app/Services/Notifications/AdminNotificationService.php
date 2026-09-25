<?php

namespace App\Services\Notifications;

use App\Models\StoreSetting;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

class AdminNotificationService
{
    public function notify(string $toggleKey, string $type, string $title, string $message, array $meta = [], ?string $url = null): void
    {
        if (! $this->isEnabled($toggleKey)) {
            return;
        }

        $admins = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'ADMIN'))
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send(
            $admins,
            new AdminAlertNotification($type, $title, $message, $meta, $url)
        );
    }

    public function isEnabled(string $toggleKey): bool
    {
        $settings = StoreSetting::first();

        if (! $settings) {
            return false;
        }

        return (bool) ($settings->{$toggleKey} ?? false);
    }

    public function unreadCountFor(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
