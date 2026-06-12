<?php

namespace App\DTO\Settings;

use App\DTO\DataTransferObject;

final readonly class SettingsData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $keyCd,
        public ?string $name,
        public string $type,
        public ?string $displayValue,
        public string $value,
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
            keyCd: trim((string) $data['key_cd']),
            name: isset($data['name']) ? trim((string) $data['name']) : null,
            type: strtoupper(trim((string) $data['type'])),
            displayValue: isset($data['display_value']) ? trim((string) $data['display_value']) : null,
            value: (string) ($data['value'] ?? ''),
            published: (bool) ($data['published'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key_cd' => $this->keyCd,
            'name' => $this->name,
            'type' => $this->type,
            'display_value' => $this->displayValue,
            'value' => $this->value,
            'published' => $this->published,
        ];
    }
}
