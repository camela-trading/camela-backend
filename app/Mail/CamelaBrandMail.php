<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CamelaBrandMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $bodyLines = [],
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public array $meta = []
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.camela.brand')
            ->with([
                'headline' => $this->headline,
                'bodyLines' => $this->bodyLines,
                'actionText' => $this->actionText,
                'actionUrl' => $this->actionUrl,
                'meta' => $this->meta,
            ]);
    }
}
