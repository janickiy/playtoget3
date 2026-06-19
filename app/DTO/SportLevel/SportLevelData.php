<?php

namespace App\DTO\SportLevel;

use App\DTO\DataTransferObject;

final readonly class SportLevelData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    /**
     * Creates DTO sport level from validated data admin form.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: trim((string) $data['name']),
        );
    }

    /**
     * Returns an array of sport level attributes to save.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
