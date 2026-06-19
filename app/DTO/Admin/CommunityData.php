<?php

namespace App\DTO\Admin;

use App\DTO\DataTransferObject;
use App\Enums\CommunityStatus;

final readonly class CommunityData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public string $about,
        public string $avatar,
        public string $coverPage,
        public string $place,
        public string $sportType,
        public int $status,
        public int $recommended,
    ) {
    }

    /**
     * Creates DTO community from validated data form admin panel.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            type: (string) $data['type'],
            name: trim((string) $data['name']),
            about: trim((string) ($data['about'] ?? '')),
            avatar: trim((string) ($data['avatar'] ?? '')),
            coverPage: trim((string) ($data['cover_page'] ?? '')),
            place: trim((string) ($data['place'] ?? '')),
            sportType: trim((string) ($data['sport_type'] ?? '')),
            status: (int) ($data['status'] ?? CommunityStatus::New->value),
            recommended: (int) ($data['recommended'] ?? 0),
        );
    }

    /**
     * Returns an array of fields to save the model.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'about' => $this->about,
            'avatar' => $this->avatar,
            'cover_page' => $this->coverPage,
            'place' => $this->place,
            'sport_type' => $this->sportType,
            'status' => $this->status,
            'recommended' => $this->recommended,
        ];
    }
}
