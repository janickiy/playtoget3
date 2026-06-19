<?php

namespace App\Service;

use App\Mail\EventInvitationMail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class EventInvitationNotificationService
{
    /**
     * Sends emails to selected users with an invitation to an event.
     *
     * @param Event $event
     * @param User $inviter
     * @param Collection<int, User> $invitees
     * @return void
     */
    public function sendInvitations(Event $event, User $inviter, Collection $invitees): void
    {
        $invitees->each(function (User $invitee) use ($event, $inviter): void {
            if (! $invitee->email) {
                return;
            }

            Mail::to($invitee->email)->send(new EventInvitationMail($event, $inviter, $invitee));
        });
    }
}
