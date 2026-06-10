<?php

namespace App\DTO\Profile;

use App\DTO\DataTransferObject;

final readonly class CommentData implements DataTransferObject
{
    public function __construct(
        public string $commentableType,
        public int $contentId,
        public string $content,
        public int $parentId = 0,
        public mixed $attach = [],
        public string $behalfableType = '',
        public int $behalfId = 0,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            commentableType: (string) ($data['commentable_type'] ?? 'user'),
            contentId: (int) ($data['content_id'] ?? 0),
            content: trim((string) ($data['comment'] ?? '')),
            parentId: (int) ($data['parent_id'] ?? 0),
            attach: $data['attach'] ?? [],
            behalfableType: (string) ($data['behalfable_type'] ?? ''),
            behalfId: (int) ($data['behalf_id'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'commentable_type' => $this->commentableType,
            'content_id' => $this->contentId,
            'comment' => $this->content,
            'parent_id' => $this->parentId,
            'attach' => $this->attach,
            'behalfable_type' => $this->behalfableType,
            'behalf_id' => $this->behalfId,
        ];
    }
}
