<?php

namespace App\DTO\Announcement;

use App\DTO\DataTransferObject;

final readonly class AnnouncementData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $title,
        public string $text,
        public ?string $slug,
        public bool $published,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            title: trim((string) $data['title']),
            text: (string) ($data['text'] ?? ''),
            slug: isset($data['slug']) && trim((string) $data['slug']) !== ''
                ? trim((string) $data['slug'])
                : null,
            published: (bool) ($data['published'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'text' => $this->text,
            'slug' => $this->slug,
            'published' => $this->published,
        ];
    }
}
