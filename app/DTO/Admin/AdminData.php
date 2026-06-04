<?php

namespace App\DTO\Admin;

use App\DTO\DataTransferObject;

final readonly class AdminData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $login,
        public string $name,
        public ?string $role = null,
        public ?string $password = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            login: (string) $data['login'],
            name: (string) $data['name'],
            role: $data['role'] ?? null,
            password: !empty($data['password']) ? (string) $data['password'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'login' => $this->login,
            'name' => $this->name,
        ];

        if ($this->role !== null) {
            $data['role'] = $this->role;
        }

        return $data;
    }
}
