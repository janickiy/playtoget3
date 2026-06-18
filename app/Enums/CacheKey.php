<?php

namespace App\Enums;

enum CacheKey: string
{
    case Settings = 'app.settings';
    case Menu = 'app.menu';

    public const TTL_SECONDS = 300;

    public function ttl(): int
    {
        return self::TTL_SECONDS;
    }
}
