<?php

namespace App\DTO\SportBlock;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class SportBlockData implements DataTransferObject
{
    public function __construct(
        public string $name,
        public string $about,
        public int $cityId,
        public string $place,
        public string $address,
        public string $phone,
        public string $email,
        public string $website,
        public ?UploadedFile $avatarFile = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
            about: trim((string) ($data['about'] ?? '')),
            cityId: (int) ($data['id_place'] ?? $data['city_id'] ?? 0),
            place: trim((string) ($data['place'] ?? '')),
            address: trim((string) ($data['address'] ?? '')),
            phone: trim((string) ($data['phone'] ?? '')),
            email: trim((string) ($data['email'] ?? '')),
            website: trim((string) ($data['website'] ?? '')),
            avatarFile: $data['avatar_file'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'about' => $this->about,
            'city_id' => $this->cityId,
            'place' => $this->place,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
        ];
    }
}
