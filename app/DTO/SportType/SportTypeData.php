<?php

namespace App\DTO\SportType;

use App\DTO\DataTransferObject;

final readonly class SportTypeData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $parentId,
    ) {
    }

    /**
     * Creates DTO sport type from validated data admin form.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $parentId = $data['parent_id'] ?? null;

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: trim((string) $data['name']),
            parentId: $parentId !== null && $parentId !== '' ? (int) $parentId : null,
        );
    }

    /**
     * Returns an array of sport type attributes to save.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->parentId,
        ];
    }
}
