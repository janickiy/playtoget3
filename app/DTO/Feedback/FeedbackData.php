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
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $subject = trim((string) ($data['subject'] ?? ''));

        return new self(
            $subject !== '' ? $subject : null,
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['message'] ?? '')),
        );
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function message(): string
    {
        return $this->message;
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
