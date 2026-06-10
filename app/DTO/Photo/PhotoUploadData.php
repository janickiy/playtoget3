<?php

namespace App\DTO\Photo;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class PhotoUploadData implements DataTransferObject
{
    public function __construct(
        public UploadedFile $file,
        public int $albumId = 0,
        public string $description = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
            albumId: (int) ($data['categorie'] ?? $data['album_id'] ?? 0),
            description: trim((string) ($data['description'] ?? '')),
        );
    }

    public function toArray(): array
    {
        return [
            'album_id' => $this->albumId,
            'description' => $this->description,
        ];
    }
}
