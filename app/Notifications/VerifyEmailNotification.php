<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
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
            ->subject('Verify your Camela Group account')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thank you for creating a Camela Group account.')
            ->line('For additional account security, you may verify your email address.')
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not create this account, you may safely ignore this email.')
            ->salutation('Regards, Camela Group');
    }
}
