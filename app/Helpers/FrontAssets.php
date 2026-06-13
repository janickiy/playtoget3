<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Community;
use App\Models\SportBlock;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FrontAssets
{
    public static function userAvatar(?User $user): string
    {
        if (! $user || ! $user->isActive()) {
            return asset('frontend/images/noimage.png');
        }

        if ($user->avatar && ($url = self::publicImageUrl('user/avatar/' . $user->avatar))) {
            return $url;
        }

        return asset($user->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    public static function adminUserAvatar(?User $user): string
    {
        if (! $user) {
            return asset('frontend/images/noimage.png');
        }

        if ($user->avatar && ($url = self::publicImageUrl('user/avatar/' . $user->avatar))) {
            return $url;
        }

        return asset($user->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    public static function userCover(?User $user): string
    {
        if ($user && $user->cover_page && ($url = self::publicImageUrl('user/cover_page/' . $user->cover_page))) {
            return $url;
        }

        return asset('frontend/images/content-bg.png');
    }

    public static function eventCover(?Event $event): string
    {
        if ($event && $event->cover_page && ($url = self::publicImageUrl('events/cover_page/' . $event->cover_page))) {
            return $url;
        }

        return asset('frontend/images/content-bg.png');
    }

    public static function eventAvatar(?Event $event): string
    {
        if ($event && $event->cover_page && ($url = self::publicImageUrl('events/cover_page/' . $event->cover_page))) {
            return $url;
        }

        return asset('frontend/images/noimage.png');
    }

    public static function communityAvatar(?Community $community): string
    {
        if ($community && ! $community->banned && $community->avatar) {
            $paths = match ($community->type) {
                'team' => [
                    'groupcontent/avatar/' . $community->avatar,
                    'teamcontent/avatar/' . $community->avatar,
                ],
                'group' => [
                    'groupcontent/avatar/' . $community->avatar,
                ],
                default => [
                    $community->type . 'content/avatar/' . $community->avatar,
                ],
            };

            foreach ($paths as $path) {
                if ($url = self::publicImageUrl($path)) {
                    return $url;
                }
            }
        }

        return asset('frontend/images/noimage.png');
    }

    public static function communityCover(?Community $community): string
    {
        if ($community && ! $community->banned && $community->cover_page && ($url = self::publicImageUrl($community->type . 'content/cover_page/' . $community->cover_page))) {
            return $url;
        }

        return asset('frontend/images/content-bg.png');
    }

    public static function sportBlockAvatar(?SportBlock $sportBlock): string
    {
        if ($sportBlock && $sportBlock->avatar && ($url = self::publicImageUrl('sportblocks/avatar/' . $sportBlock->avatar))) {
            return $url;
        }

        return asset('frontend/images/noimage.png');
    }

    public static function photoGallery(?Photo $photo, string $field = 'small_photo'): ?string
    {
        if (! $photo) {
            return null;
        }

        $file = $photo->{$field} ?: $photo->photo;

        if (! $file) {
            return null;
        }

        $type = $photo->album?->photoalbumable_type ?: 'user';
        $paths = [
            "photogallery/{$type}/{$file}",
            "photogallery/user_attach/{$file}",
            "photogallery/user/{$file}",
            "attachments/comment/{$file}",
            "attachments/message/{$file}",
        ];

        foreach ($paths as $path) {
            if ($url = self::publicImageUrl($path)) {
                return $url;
            }
        }

        return null;
    }

    private static function publicImageUrl(string $path): ?string
    {
        $path = 'images/' . ltrim($path, '/');

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $legacyPath = 'uploads/' . $path;

        return is_file(public_path($legacyPath))
            ? asset($legacyPath)
            : null;
    }
}
