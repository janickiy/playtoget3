<?php

namespace App\DTO\Album;

use App\DTO\DataTransferObject;

final readonly class AlbumData implements DataTransferObject
{
    public function __construct(
        public string $name,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
