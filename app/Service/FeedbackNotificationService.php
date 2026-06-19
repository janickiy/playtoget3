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
     * Sends a letter to the user confirming that the feedback request has been accepted.
     */
    public function sendSubmittedNotification(FeedbackData $data): void
    {
        Mail::to($data->email())->send(new FeedbackSubmitted($data));
    }

    /**
     * Sends a notification to the user about the status change of the request.
     */
    public function sendStatusChangedNotification(Feedback $feedback, FeedbackStatus $previousStatus): void
    {
        if (! $feedback->email) {
            return;
        }

        Mail::to($feedback->email)->send(new FeedbackStatusChanged($feedback, $previousStatus));
    }
}
