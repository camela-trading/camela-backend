<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UnverifiedAccountReminderNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Verify your Camela Group email address')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You created your Camela Group account a few days ago, but your email address has not yet been verified.')
            ->line('You can continue using the store normally, but verifying your email helps confirm your account and ensures you can securely receive important account communications.')
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not create this account, you may ignore this email.')
            ->salutation('Thank you, Camela Group');
    }
}
