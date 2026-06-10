<?php

namespace App\DTO\Profile;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class ProfileSettingsData implements DataTransferObject
{
    public function __construct(
        public array $user,
        public ?string $temporaryAvatar,
        public ?string $temporaryCover,
        public ?UploadedFile $coverFile = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            user: $data['user'] ?? [],
            temporaryAvatar: $data['file_ava'] ?? null,
            temporaryCover: $data['file_cover'] ?? null,
            coverFile: $data['cover'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'file_ava' => $this->temporaryAvatar,
            'file_cover' => $this->temporaryCover,
        ];
    }
}
