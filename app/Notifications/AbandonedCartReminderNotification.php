<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbandonedCartReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly int $itemCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('services.frontend_url', config('app.url')), '/');

        return (new MailMessage)
            ->subject('You still have items in your Camela Group cart')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("You still have {$this->itemCount} ".str('item')->plural($this->itemCount).' waiting in your Camela Group shopping cart.')
            ->line('If you are still interested, you can return to your cart and continue your purchase.')
            ->action('View My Cart', $frontendUrl.'/cart')
            ->line('Product availability may change, and current pricing is confirmed at checkout.')
            ->salutation('Thank you, Camela Group');
    }
}
