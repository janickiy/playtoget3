<?php

namespace App\DTO\Community;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class CommunityData implements DataTransferObject
{
    public function __construct(
        public string $name,
        public string $about,
        public int $cityId,
        public int $sportId,
        public string $place,
        public string $sportType,
        public int $permissionWall,
        public int $permissionPhoto,
        public int $permissionVideo,
        public int $type,
        public ?UploadedFile $avatarFile = null,
        public ?UploadedFile $coverFile = null,
    ) {
    }

    public static function fromArray(array $data, bool $withSettings = false): self
    {
        $settings = $data['community'] ?? [];

        return new self(
            name: trim((string) $data['name']),
            about: trim((string) ($data['about'] ?? '')),
            cityId: (int) ($data['id_place'] ?? $data['city_id'] ?? 0),
            sportId: (int) ($data['id_sport'] ?? $data['sport_id'] ?? 0),
            place: trim((string) ($data['place'] ?? '')),
            sportType: trim((string) ($data['sport'] ?? $data['sport_type'] ?? '')),
            permissionWall: $withSettings ? (int) ($settings['permission_wall'] ?? 0) : 0,
            permissionPhoto: $withSettings ? (int) ($settings['permission_photo'] ?? 0) : 0,
            permissionVideo: $withSettings ? (int) ($settings['permission_video'] ?? 0) : 0,
            type: $withSettings ? (int) ($settings['type'] ?? 0) : 0,
            avatarFile: $data['avatar_file'] ?? null,
            coverFile: $data['cover_file'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'about' => $this->about,
            'city_id' => $this->cityId,
            'sport_id' => $this->sportId,
            'place' => $this->place,
            'sport_type' => $this->sportType,
            'permission_wall' => $this->permissionWall,
            'permission_photo' => $this->permissionPhoto,
            'permission_video' => $this->permissionVideo,
            'type' => $this->type,
        ];
    }
}
