<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\SportBlock;
use App\Models\User;

class FrontAssets
{
    public static function userAvatar(?User $user): string
    {
        if (! $user || $user->banned || $user->deleted) {
            return asset('templates/images/noimage.png');
        }

        if ($user->avatar && file_exists(public_path('uploads/images/user/avatar/' . $user->avatar))) {
            return asset('uploads/images/user/avatar/' . $user->avatar);
        }

        return asset($user->sex === 'female' ? 'templates/images/default_female.png' : 'templates/images/default_male.png');
    }

    public static function userCover(?User $user): string
    {
        if ($user && $user->cover_page && file_exists(public_path('uploads/images/user/cover_page/' . $user->cover_page))) {
            return asset('uploads/images/user/cover_page/' . $user->cover_page);
        }

        return asset('templates/images/content-bg.png');
    }

    public static function eventCover(?Event $event): string
    {
        if ($event && $event->cover_page && file_exists(public_path('uploads/images/events/cover_page/' . $event->cover_page))) {
            return asset('uploads/images/events/cover_page/' . $event->cover_page);
        }

        return asset('templates/images/content-bg.png');
    }

    public static function sportBlockAvatar(?SportBlock $sportBlock): string
    {
        if ($sportBlock && $sportBlock->avatar && file_exists(public_path('uploads/images/sportblocks/avatar/' . $sportBlock->avatar))) {
            return asset('uploads/images/sportblocks/avatar/' . $sportBlock->avatar);
        }

        return asset('templates/images/noimage.png');
    }
}
