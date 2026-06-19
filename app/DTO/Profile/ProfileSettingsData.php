<?php

namespace App\DTO\Profile;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class ProfileSettingsData implements DataTransferObject
{
    public function __construct(
        public array $user,
        public ?ProfilePersonalData $profile,
        public ?string $temporaryAvatar,
        public ?string $temporaryCover,
        public ?UploadedFile $coverFile = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            user: $data['user'] ?? [],
            profile: isset($data['profile']) && is_array($data['profile'])
                ? ProfilePersonalData::fromArray($data['profile'])
                : null,
            temporaryAvatar: $data['file_ava'] ?? null,
            temporaryCover: $data['file_cover'] ?? null,
            coverFile: $data['cover'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'profile' => $this->profile?->toArray(),
            'file_ava' => $this->temporaryAvatar,
            'file_cover' => $this->temporaryCover,
        ];
    }
}
