<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $confirmationUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('profile.settings.delete_account.mail_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profile.account-deletion-confirmation',
        );
    }
}
