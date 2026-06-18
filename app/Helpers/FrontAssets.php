<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Community;
use App\Models\SportBlock;
use App\Models\User;
use App\Support\MediaPath;
use Illuminate\Support\Facades\Storage;

class FrontAssets
{
    public static function userAvatar(?User $user): string
    {
        if (! $user || ! $user->isActive()) {
            return asset('frontend/images/noimage.png');
        }

        if ($user->avatar && ($url = self::publicImageUrl(MediaPath::directory('user_avatar', $user->avatar)))) {
            return $url;
        }

        return asset($user->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    public static function adminUserAvatar(?User $user): string
    {
        if (! $user) {
            return asset('frontend/images/noimage.png');
        }

        if ($user->avatar && ($url = self::publicImageUrl(MediaPath::directory('user_avatar', $user->avatar)))) {
            return $url;
        }

        return asset($user->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    public static function userCover(?User $user): string
    {
        if ($user && $user->cover_page && ($url = self::publicImageUrl(MediaPath::directory('user_cover', $user->cover_page)))) {
            return $url;
        }

        return asset('frontend/images/content-bg.png');
    }

    public static function eventCover(?Event $event): string
    {
        if ($event && $event->cover_page && ($url = self::publicImageUrl(MediaPath::directory('event_cover', $event->cover_page)))) {
            return $url;
        }

        return asset('frontend/images/content-bg.png');
    }

    public static function eventAvatar(?Event $event): string
    {
        if ($event && $event->cover_page && ($url = self::publicImageUrl(MediaPath::directory('event_cover', $event->cover_page)))) {
            return $url;
        }

        return asset('frontend/images/noimage.png');
    }

    public static function communityAvatar(?Community $community): string
    {
        if (! $community || ! $community->isVisible()) {
            return asset('frontend/images/noimage.png');
        }

        return self::adminCommunityAvatar($community);
    }

    public static function adminCommunityAvatar(?Community $community): string
    {
        if ($community && $community->avatar) {
            $paths = match ($community->type) {
                'team' => [
                    MediaPath::communityRelative('group', MediaPath::communityAvatarDirectory(), $community->avatar),
                    MediaPath::communityRelative('team', MediaPath::communityAvatarDirectory(), $community->avatar),
                ],
                'group' => [
                    MediaPath::communityRelative('group', MediaPath::communityAvatarDirectory(), $community->avatar),
                ],
                default => [
                    MediaPath::communityRelative($community->type, MediaPath::communityAvatarDirectory(), $community->avatar),
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
        if (
            $community
            && $community->isVisible()
            && $community->cover_page
            && ($url = self::publicImageUrl(MediaPath::communityRelative(
                $community->type,
                MediaPath::communityCoverDirectory(),
                $community->cover_page,
            )))
        ) {
            return $url;
        }

        return asset('frontend/images/content-bg.png');
    }

    public static function sportBlockAvatar(?SportBlock $sportBlock): string
    {
        if ($sportBlock && $sportBlock->avatar && ($url = self::publicImageUrl(MediaPath::directory('sport_block_avatar', $sportBlock->avatar)))) {
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
            MediaPath::galleryRelative($type, $file),
            MediaPath::directory('gallery_user_attach', $file),
            MediaPath::directory('gallery_user', $file),
            MediaPath::directory('attachment_comment', $file),
            MediaPath::directory('attachment_message', $file),
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
