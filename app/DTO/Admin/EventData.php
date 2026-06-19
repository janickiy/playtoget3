<?php

namespace App\DTO\Admin;

use App\DTO\DataTransferObject;
use App\Enums\EventStatus;
use Carbon\CarbonImmutable;

final readonly class EventData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $dateFrom,
        public ?string $dateTo,
        public string $description,
        public string $sportType,
        public string $coverPage,
        public string $place,
        public string $address,
        public int $status,
    ) {
    }

    /**
     * Creates DTO event from validated data form admin panel.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: trim((string) $data['name']),
            dateFrom: self::nullableDate($data['date_from'] ?? null),
            dateTo: self::nullableDate($data['date_to'] ?? null),
            description: trim((string) ($data['description'] ?? '')),
            sportType: trim((string) ($data['sport_type'] ?? '')),
            coverPage: trim((string) ($data['cover_page'] ?? '')),
            place: trim((string) ($data['place'] ?? '')),
            address: trim((string) ($data['address'] ?? '')),
            status: (int) ($data['status'] ?? EventStatus::New->value),
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
            'name' => $this->name,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'description' => $this->description,
            'sport_type' => $this->sportType,
            'cover_page' => $this->coverPage,
            'place' => $this->place,
            'address' => $this->address,
            'status' => $this->status,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function nullableDate(mixed $value): ?string
    {
        $value = self::nullableString($value);

        return $value !== null
            ? CarbonImmutable::parse($value)->format('Y-m-d H:i:s')
            : null;
    }
}
