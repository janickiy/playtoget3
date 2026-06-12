<?php

namespace App\Service;

use App\DTO\Feedback\FeedbackData;
use App\Mail\FeedbackSubmitted;
use Illuminate\Support\Facades\Mail;

class FeedbackNotificationService
{
    /**
     * Отправляет пользователю письмо с подтверждением, что обращение принято.
     */
    public function sendSubmittedNotification(FeedbackData $data): void
    {
        Mail::to($data->email())->send(new FeedbackSubmitted($data));
    }
}
