<?php

namespace App\Mail;

use App\Models\Community;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommunityInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Community $community,
        public readonly User $inviter,
        public readonly User $invitee,
    ) {
    }

    public function envelope(): Envelope
    {
        $label = $this->community->type === 'group' ? 'group' : 'team';

        return new Envelope(
            subject: 'You have been invited to ' . $label,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.community.invitation',
        );
    }
}
