<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly User $inviter,
        public readonly User $invitee,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Вас пригласили на мероприятие',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event.invitation',
        );
    }
}
