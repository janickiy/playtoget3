<?php

namespace App\DTO\Content;

use App\DTO\DataTransferObject;

final readonly class ContentData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $title,
        public string $text,
        public ?string $slug,
        public bool $published,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @return array<string, mixed>
     */
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
