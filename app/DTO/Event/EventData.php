<?php

namespace App\DTO\Event;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class EventData implements DataTransferObject
{
    public function __construct(
        public string $name,
        public string $description,
        public int $cityId,
        public string $place,
        public string $address,
        public string $sportType,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?UploadedFile $coverFile = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
            description: trim((string) ($data['description'] ?? '')),
            cityId: (int) ($data['id_place'] ?? $data['city_id'] ?? 0),
            place: trim((string) ($data['place'] ?? '')),
            address: trim((string) ($data['address'] ?? '')),
            sportType: trim((string) ($data['sport'] ?? $data['sport_type'] ?? '')),
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            coverFile: $data['cover_file'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'city_id' => $this->cityId,
            'place' => $this->place,
            'address' => $this->address,
            'sport_type' => $this->sportType,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }
}
