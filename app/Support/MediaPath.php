<?php

namespace App\Support;

use InvalidArgumentException;

final class MediaPath
{
    /**
     * Returns public-storage path for a configured media directory.
     */
    public static function storage(string $key, ?string $filename = null): string
    {
        return self::fromRelative(self::directory($key), $filename);
    }

    /**
     * Returns uploads public path for a configured media directory.
     */
    public static function uploads(string $key, ?string $filename = null): string
    {
        return self::uploadsFromRelative(self::directory($key), $filename);
    }

    /**
     * Returns configured media directory without storage root.
     */
    public static function directory(string $key, ?string $filename = null): string
    {
        $directory = config('media.directories.' . $key);

        if (! is_string($directory) || $directory === '') {
            throw new InvalidArgumentException(sprintf('Media directory "%s" is not configured.', $key));
        }

        return self::join($directory, $filename);
    }

    /**
     * Returns public-storage path for a relative media path.
     */
    public static function fromRelative(string $relativePath, ?string $filename = null): string
    {
        return self::join(self::storageRoot(), $relativePath, $filename);
    }

    /**
     * Returns uploads public path for a relative media path.
     */
    public static function uploadsFromRelative(string $relativePath, ?string $filename = null): string
    {
        return self::join(self::uploadsRoot(), $relativePath, $filename);
    }

    /**
     * Returns public-storage path for a gallery media type.
     */
    public static function gallery(?string $type = 'user', ?string $filename = null): string
    {
        return self::fromRelative(self::galleryRelative($type), $filename);
    }

    /**
     * Returns uploads public path for a gallery media type.
     */
    public static function galleryUploads(?string $type = 'user', ?string $filename = null): string
    {
        return self::uploadsFromRelative(self::galleryRelative($type), $filename);
    }

    /**
     * Returns gallery path without storage root.
     */
    public static function galleryRelative(?string $type = 'user', ?string $filename = null): string
    {
        return self::join(self::directory('gallery'), $type ?: 'user', $filename);
    }

    /**
     * Returns public-storage path for community media.
     */
    public static function community(string $kind, string $directory, ?string $filename = null): string
    {
        return self::fromRelative(self::communityRelative($kind, $directory), $filename);
    }

    /**
     * Returns uploads public path for community media.
     */
    public static function communityUploads(string $kind, string $directory, ?string $filename = null): string
    {
        return self::uploadsFromRelative(self::communityRelative($kind, $directory), $filename);
    }

    /**
     * Returns community media path without storage root.
     */
    public static function communityRelative(string $kind, string $directory, ?string $filename = null): string
    {
        return self::join($kind . self::communityContentSuffix(), $directory, $filename);
    }

    /**
     * Returns configured community avatar directory.
     */
    public static function communityAvatarDirectory(): string
    {
        return self::configString('media.community_avatar_directory');
    }

    /**
     * Returns configured community cover directory.
     */
    public static function communityCoverDirectory(): string
    {
        return self::configString('media.community_cover_directory');
    }

    /**
     * Returns configured storage root.
     */
    public static function storageRoot(): string
    {
        return self::configString('media.storage_root');
    }

    /**
     * Returns configured uploads root.
     */
    public static function uploadsRoot(): string
    {
        return self::configString('media.uploads_root');
    }

    /**
     * Returns configured community content suffix.
     */
    private static function communityContentSuffix(): string
    {
        return self::configString('media.community_content_suffix');
    }

    /**
     * Reads a non-empty string from config.
     */
    private static function configString(string $key): string
    {
        $value = config($key);

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException(sprintf('Config value "%s" is not set.', $key));
        }

        return $value;
    }

    /**
     * Joins path fragments using slash separators.
     */
    private static function join(?string ...$parts): string
    {
        $segments = [];

        foreach ($parts as $part) {
            if ($part === null || $part === '') {
                continue;
            }

            $segments[] = trim($part, '/');
        }

        return implode('/', $segments);
    }
}
