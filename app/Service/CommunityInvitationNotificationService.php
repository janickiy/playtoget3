<?php

namespace App\Service;

use App\Mail\CommunityInvitationMail;
use App\Models\Community;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class CommunityInvitationNotificationService
{
    /**
     * Отправляет выбранным пользователям письма с приглашением в команду или группу.
     *
     * @param Community $community
     * @param User $inviter
     * @param Collection<int, User> $invitees
     * @return void
     */
    public function sendInvitations(Community $community, User $inviter, Collection $invitees): void
    {
        $invitees->each(function (User $invitee) use ($community, $inviter): void {
            if (! $invitee->email) {
                return;
            }

            Mail::to($invitee->email)->send(new CommunityInvitationMail($community, $inviter, $invitee));
        });
    }
}
