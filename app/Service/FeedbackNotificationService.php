<?php

namespace App\Service;

use App\DTO\Feedback\FeedbackData;
use App\Enums\FeedbackStatus;
use App\Mail\FeedbackStatusChanged;
use App\Mail\FeedbackSubmitted;
use App\Models\Feedback;
use Illuminate\Support\Facades\Mail;

class FeedbackNotificationService
{
    /**
     * Отправляет user письмо с подтверждением, что feedback request принято.
     */
    public function sendSubmittedNotification(FeedbackData $data): void
    {
        Mail::to($data->email())->send(new FeedbackSubmitted($data));
    }

    /**
     * Отправляет user уведомление о status change обращения.
     */
    public function sendStatusChangedNotification(Feedback $feedback, FeedbackStatus $previousStatus): void
    {
        if (! $feedback->email) {
            return;
        }

        Mail::to($feedback->email)->send(new FeedbackStatusChanged($feedback, $previousStatus));
    }
}
