<?php

namespace App\DTO\Admin;

use App\DTO\DataTransferObject;

final readonly class UserData implements DataTransferObject
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $password = null,
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $secondname = null,
        public ?string $sex = null,
        public ?string $birthday = null,
        public ?string $phone = null,
        public ?string $contactEmail = null,
        public ?string $telegram = null,
        public ?string $whatsapp = null,
        public ?string $viber = null,
        public ?string $website = null,
        public ?string $about = null,
        public ?string $aboutSport = null,
        public ?string $country = null,
        public ?string $region = null,
        public ?string $city = null,
        public ?string $language = null,
        public bool $confirmed = false,
        public bool $banned = false,
        public bool $deleted = false,
    ) {
    }

    /**
     * Создает DTO пользователя из валидированных данных админской формы.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            email: (string) $data['email'],
            password: !empty($data['password']) ? (string) $data['password'] : null,
            firstname: self::nullableString($data['firstname'] ?? null),
            lastname: self::nullableString($data['lastname'] ?? null),
            secondname: self::nullableString($data['secondname'] ?? null),
            sex: self::nullableString($data['sex'] ?? null),
            birthday: self::nullableString($data['birthday'] ?? null),
            phone: self::nullableString($data['phone'] ?? null),
            contactEmail: self::nullableString($data['contact_email'] ?? null),
            telegram: self::nullableString($data['telegram'] ?? null),
            whatsapp: self::nullableString($data['whatsapp'] ?? null),
            viber: self::nullableString($data['viber'] ?? null),
            website: self::nullableString($data['website'] ?? null),
            about: self::nullableString($data['about'] ?? null),
            aboutSport: self::nullableString($data['about_sport'] ?? null),
            country: self::nullableString($data['country'] ?? null),
            region: self::nullableString($data['region'] ?? null),
            city: self::nullableString($data['city'] ?? null),
            language: self::nullableString($data['language'] ?? null),
            confirmed: self::boolean($data['confirmed'] ?? false),
            banned: self::boolean($data['banned'] ?? false),
            deleted: self::boolean($data['deleted'] ?? false),
        );
    }

    /**
     * Возвращает массив атрибутов пользователя без пароля.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'secondname' => $this->secondname,
            'sex' => $this->sex,
            'birthday' => $this->birthday,
            'phone' => $this->phone,
            'contact_email' => $this->contactEmail,
            'telegram' => $this->telegram,
            'whatsapp' => $this->whatsapp,
            'viber' => $this->viber,
            'website' => $this->website,
            'about' => $this->about,
            'about_sport' => $this->aboutSport,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'language' => $this->language,
            'confirmed' => $this->confirmed,
            'banned' => $this->banned,
            'deleted' => $this->deleted,
        ];
    }

    /**
     * Нормализует пустые строки формы в null.
     */
    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * Приводит чекбоксы формы к boolean-значению.
     */
    private static function boolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
