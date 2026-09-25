<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MembershipAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $application,
    ) {}

    public function build()
    {
        return $this->subject('New Membership Application')
            ->view('emails.membership.admin')
            ->with([
                'application' => $this->application,
            ]);
    }
}
