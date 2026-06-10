<?php

namespace App\DTO\Video;

use App\DTO\DataTransferObject;

final readonly class VideoData implements DataTransferObject
{
    public function __construct(
        public int $albumId,
        public string $link,
        public string $description,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            albumId: (int) $data['videoalbum_id'],
            link: trim((string) $data['video']),
            description: trim((string) ($data['description'] ?? '')),
        );
    }

    public function toArray(): array
    {
        return [
            'videoalbum_id' => $this->albumId,
            'video' => $this->link,
            'description' => $this->description,
        ];
    }
}
