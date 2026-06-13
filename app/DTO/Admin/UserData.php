<?php

namespace App\DTO\Admin;

use App\DTO\DataTransferObject;
use App\Enums\UserStatus;

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
        public int $status = 0,
        public ?string $confirmedAt = null,
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
            status: (int) ($data['status'] ?? UserStatus::New->value),
            confirmedAt: self::nullableString($data['confirmed_at'] ?? null),
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
            'status' => $this->status,
            'confirmed_at' => $this->confirmedAt,
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

}
