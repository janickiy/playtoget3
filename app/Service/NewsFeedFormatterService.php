<?php

namespace App\Service;

use App\Enums\UserStatus;
use Illuminate\Support\Facades\Storage;

class NewsFeedFormatterService
{
    /**
     * Возвращает URL аватара пользователя или стандартную заглушку.
     *
     * @param object $row
     * @return string
     */
    public function userAvatar(object $row): string
    {
        if ((int) $row->status !== UserStatus::Confirmed->value) {
            return asset('frontend/images/noimage.png');
        }

        if ($row->avatar && ($url = $this->publicImageUrl('user/avatar/' . $row->avatar))) {
            return $url;
        }

        return asset($row->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    /**
     * Возвращает отображаемое имя владельца записи.
     *
     * @param object $row
     * @return string
     */
    public function ownerName(object $row): string
    {
        $name = trim(sprintf('%s %s', (string) $row->owner_firstname, (string) $row->owner_lastname));

        return $name !== '' ? $name : (string) $row->owner_email;
    }

    /**
     * Возвращает URL аватара владельца или стандартную заглушку.
     *
     * @param object $row
     * @return string
     */
    public function ownerAvatar(object $row): string
    {
        if ((int) $row->owner_status !== UserStatus::Confirmed->value) {
            return asset('frontend/images/noimage.png');
        }

        if ($row->owner_avatar && ($url = $this->publicImageUrl('user/avatar/' . $row->owner_avatar))) {
            return $url;
        }

        return asset($row->owner_sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    /**
     * Декодирует и экранирует текст для безопасного вывода.
     *
     * @param string $value
     * @return string
     */
    public function safeText(string $value): string
    {
        return e(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Возвращает URL фотографии или стандартную заглушку.
     *
     * @param string|null $file
     * @param string|null $type
     * @return string
     */
    public function photoUrl(?string $file, ?string $type): string
    {
        if (! $file) {
            return asset('frontend/images/noimage.png');
        }

        $type = $type ?: 'user';
        $paths = [
            "photogallery/{$type}/{$file}",
            "photogallery/user_attach/{$file}",
            "photogallery/user/{$file}",
        ];

        foreach ($paths as $path) {
            if ($url = $this->publicImageUrl($path)) {
                return $url;
            }
        }

        return asset('frontend/images/noimage.png');
    }

    /**
     * Возвращает публичный URL изображения из storage, если файл существует.
     *
     * @param string $path
     * @return string|null
     */
    public function publicImageUrl(string $path): ?string
    {
        $path = 'images/' . ltrim($path, '/');

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

}
