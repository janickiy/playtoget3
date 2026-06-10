<?php

namespace App\DTO\Message;

use App\DTO\DataTransferObject;

final readonly class MessageData implements DataTransferObject
{
    public function __construct(
        public string $content,
        public array|string|null $attach = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            content: trim((string) ($data['message'] ?? '')),
            attach: $data['attach'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->content,
            'attach' => $this->attach,
        ];
    }
}
