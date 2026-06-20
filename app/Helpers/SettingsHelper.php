<?php

namespace App\Helpers;

use App\Enums\CacheKey;
use App\Models\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    private static $instance;

    private $settings;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
            self::$instance->loadSettings();
        }

        return self::$instance;
    }

    /**
     * @return array
     */
    private function loadSettings(): mixed
    {
        $this->settings = self::loadSettingsFromCache(true);

        return $this->settings;
    }

    /**
     * @param bool $cache
     * @return array
     */
    private static function loadSettingsFromCache(bool $cache = false): mixed
    {
        if ($cache === true) {
            return Cache::remember(CacheKey::Settings->value, CacheKey::Settings->ttl(), function () {
                try {
                    $settings = Settings::all();
                } catch (QueryException $e) {
                    return [];
                }

                if ($settings === null) {
                    return [];
                }

                return $settings->pluck('value', 'key_cd')->toArray();
            });
        } else {
            try {
                $settings = Settings::all();
            } catch (QueryException $e) {
                return [];
            }

            return $settings->pluck('value', 'key_cd')->toArray();
        }
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return bool|mixed
     */
    public static function getValueForKey(string $name, mixed $default = false): mixed
    {
        $settings = SettingsHelper::getInstance()->settings;

        return $settings[$name] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return bool|mixed
     */
    public static function get(string $key, mixed $default = false): mixed
    {
        return self::getValueForKey($key, $default);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::getInstance()->settings[$key]);
    }

    /**
     * Returns whether a numeric or boolean setting is enabled.
     */
    public static function enabled(string $key): bool
    {
        return filter_var(self::get($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param bool $reload
     * @return bool
     */
    public static function cacheClear(bool $reload = false): bool
    {
        $result = Cache::forget(CacheKey::Settings->value);
        self::$instance = null;

        if ($reload) {
            self::getInstance();
        }

        return $result;
    }
}
