<?php

namespace App\DTO\Feedback;

use App\DTO\DataTransferObject;

readonly class FeedbackData implements DataTransferObject
{
    public function __construct(
        private ?string $subject,
        private string $name,
        private string $email,
        private string $message,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
        ];
    }
}
