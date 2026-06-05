<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\SportBlock;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FrontAssets
{
    public static function userAvatar(?User $user): string
    {
        if (! $user || $user->banned || $user->deleted) {
            return asset('templates/images/noimage.png');
        }

        if ($user->avatar && ($url = self::publicImageUrl('user/avatar/' . $user->avatar))) {
            return $url;
        }

        return asset($user->sex === 'female' ? 'templates/images/default_female.png' : 'templates/images/default_male.png');
    }

    public static function userCover(?User $user): string
    {
        if ($user && $user->cover_page && ($url = self::publicImageUrl('user/cover_page/' . $user->cover_page))) {
            return $url;
        }

        return asset('templates/images/content-bg.png');
    }

    public static function eventCover(?Event $event): string
    {
        if ($event && $event->cover_page && ($url = self::publicImageUrl('events/cover_page/' . $event->cover_page))) {
            return $url;
        }

        return asset('templates/images/content-bg.png');
    }

    public static function sportBlockAvatar(?SportBlock $sportBlock): string
    {
        if ($sportBlock && $sportBlock->avatar && ($url = self::publicImageUrl('sportblocks/avatar/' . $sportBlock->avatar))) {
            return $url;
        }

        return asset('templates/images/noimage.png');
    }

    private static function publicImageUrl(string $path): ?string
    {
        $path = 'images/' . ltrim($path, '/');

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }
}
