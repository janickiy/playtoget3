<?php

namespace App\Helpers;

use Carbon\Carbon;

class StringHelper
{
    /**
     * @param string $provider
     * @param string $video
     * @return string
     */
    public static function thumbUrl(string $provider, string $video): string
    {
        if ($provider === 'youtube' && $video !== '') {
            return 'https://img.youtube.com/vi/' . rawurlencode($video) . '/hqdefault.jpg';
        }

        return asset('frontend/images/default_group.png');
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public static function russianDate(Carbon $date): string
    {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        ];

        return $date->day . ' ' . $months[$date->month] . ' ' . $date->year;
    }
}
