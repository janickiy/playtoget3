<?php

namespace App\Service;

use App\Enums\UserStatus;
use App\Support\MediaPath;
use Illuminate\Support\Facades\Storage;

class NewsFeedFormatterService
{
    /**
     * Returns URL avatarа user or default заглушку.
     *
     * @param object $row
     * @return string
     */
    public function userAvatar(object $row): string
    {
        if ((int) $row->status !== UserStatus::Confirmed->value) {
            return asset('frontend/images/noimage.png');
        }

        if ($row->avatar && ($url = $this->publicImageUrl(MediaPath::directory('user_avatar', $row->avatar)))) {
            return $url;
        }

        return asset($row->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    /**
     * Returns отображаемое name владельца записи.
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
     * Returns URL avatarа владельца or default заглушку.
     *
     * @param object $row
     * @return string
     */
    public function ownerAvatar(object $row): string
    {
        if ((int) $row->owner_status !== UserStatus::Confirmed->value) {
            return asset('frontend/images/noimage.png');
        }

        if ($row->owner_avatar && ($url = $this->publicImageUrl(MediaPath::directory('user_avatar', $row->owner_avatar)))) {
            return $url;
        }

        return asset($row->owner_sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    /**
     * Декодирует и экранирует текст для безопасного output.
     *
     * @param string $value
     * @return string
     */
    public function safeText(string $value): string
    {
        return e(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Returns URL photo or default заглушку.
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
            MediaPath::galleryRelative($type, $file),
            MediaPath::directory('gallery_user_attach', $file),
            MediaPath::directory('gallery_user', $file),
        ];

        foreach ($paths as $path) {
            if ($url = $this->publicImageUrl($path)) {
                return $url;
            }
        }

        return asset('frontend/images/noimage.png');
    }

    /**
     * Returns публичный URL image из storage, если файл существует.
     *
     * @param string $path
     * @return string|null
     */
    public function publicImageUrl(string $path): ?string
    {
        $relativePath = ltrim($path, '/');
        $path = MediaPath::fromRelative($relativePath);

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $uploadsPath = MediaPath::uploadsFromRelative($relativePath);

        return is_file(public_path($uploadsPath))
            ? asset($uploadsPath)
            : null;
    }

}
