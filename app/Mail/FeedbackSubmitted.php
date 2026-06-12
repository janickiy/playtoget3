<?php

namespace App\Mail;

use App\DTO\Feedback\FeedbackData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackSubmitted extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly FeedbackData $feedback)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ваше сообщение отправлено',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback.submitted',
        );
    }
}
