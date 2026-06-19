<?php

namespace App\DTO\Profile;

use App\DTO\DataTransferObject;

final readonly class ProfilePersonalData implements DataTransferObject
{
    public function __construct(
        public ?string $nickname = null,
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $sex = null,
        public ?string $birthday = null,
        public ?string $about = null,
        public ?string $aboutSport = null,
        public ?string $country = null,
        public ?string $region = null,
    ) {
    }

    /**
     * Creates DTO from validated profile form data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nickname: self::nullableString($data['nickname'] ?? null),
            firstname: self::nullableString($data['firstname'] ?? null),
            lastname: self::nullableString($data['lastname'] ?? null),
            sex: self::nullableString($data['sex'] ?? null),
            birthday: self::nullableString($data['birthday'] ?? null),
            about: self::nullableString($data['about'] ?? null),
            aboutSport: self::nullableString($data['about_sport'] ?? null),
            country: self::nullableString($data['country'] ?? null),
            region: self::nullableString($data['region'] ?? null),
        );
    }

    /**
     * Returns user attributes for repository update.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nickname' => $this->nickname,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'sex' => $this->sex,
            'birthday' => $this->birthday,
            'about' => $this->about,
            'about_sport' => $this->aboutSport,
            'country' => $this->country,
            'region' => $this->region,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
