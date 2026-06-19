<?php

namespace App\DTO\Admin;

use App\DTO\DataTransferObject;
use App\Enums\SportBlockStatus;

final readonly class SportBlockData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public string $about,
        public string $place,
        public string $address,
        public string $phone,
        public string $email,
        public string $avatar,
        public string $website,
        public ?int $ownerId,
        public int $status,
        public int $recommended,
    ) {
    }

    /**
     * Creates DTO sport block from validated data form admin panel.
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
            place: trim((string) ($data['place'] ?? '')),
            address: trim((string) ($data['address'] ?? '')),
            phone: trim((string) ($data['phone'] ?? '')),
            email: trim((string) ($data['email'] ?? '')),
            avatar: trim((string) ($data['avatar'] ?? '')),
            website: trim((string) ($data['website'] ?? '')),
            ownerId: isset($data['owner_id']) && (int) $data['owner_id'] > 0 ? (int) $data['owner_id'] : null,
            status: (int) ($data['status'] ?? SportBlockStatus::New->value),
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
            'place' => $this->place,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'website' => $this->website,
            'owner_id' => $this->ownerId,
            'status' => $this->status,
            'recommended' => $this->recommended,
        ];
    }
}
