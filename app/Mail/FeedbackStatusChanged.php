<?php

namespace App\Mail;

use App\Enums\FeedbackStatus;
use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackStatusChanged extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Feedback $feedback,
        public readonly FeedbackStatus $previousStatus,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Статус обращения изменен',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback.status-changed',
        );
    }
}
