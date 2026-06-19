<?php

namespace App\DTO\Admin\Feedback;

use App\DTO\DataTransferObject;
use App\Enums\FeedbackStatus;

final readonly class FeedbackAdminData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public int $status,
        public ?string $answer,
    ) {
    }

    /**
     * Creates DTO for admin update of request from validated data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $answer = trim((string) ($data['answer'] ?? ''));

        return new self(
            id: (int) ($data['id'] ?? 0),
            status: (int) ($data['status'] ?? FeedbackStatus::New->value),
            answer: $answer !== '' ? $answer : null,
        );
    }

    /**
     * Returns an array of request fields to save.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'answer' => $this->answer,
        ];
    }
}
