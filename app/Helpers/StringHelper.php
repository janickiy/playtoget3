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
     * @param string|null $provider
     * @param string|null $video
     * @return string
     */
    public static function videoThumbUrl(?string $provider, ?string $video): string
    {
        if ($provider === 'youtube' && $video) {
            return 'https://img.youtube.com/vi/' . rawurlencode($video) . '/hqdefault.jpg';
        }

        return asset('frontend/images/noimage.png');
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

    /**
     * @param string $text
     * @param bool $toLower
     * @return string
     */
    public static function slug(string $text, bool $toLower = true): string
    {
        $text = trim($text);

        $tr = [
            "А" => "A",
            "Б" => "B",
            "В" => "V",
            "Г" => "G",
            "Д" => "D",
            "Е" => "E",
            "Ё" => "E",
            "Ж" => "J",
            "З" => "Z",
            "И" => "I",
            "Й" => "Y",
            "К" => "K",
            "Л" => "L",
            "М" => "M",
            "Н" => "N",
            "О" => "O",
            "П" => "P",
            "Р" => "R",
            "С" => "S",
            "Т" => "T",
            "У" => "U",
            "Ф" => "F",
            "Х" => "H",
            "Ц" => "TS",
            "Ч" => "CH",
            "Ш" => "SH",
            "Щ" => "SCH",
            "Ъ" => "",
            "Ы" => "YI",
            "Ь" => "",
            "Э" => "E",
            "Ю" => "YU",
            "Я" => "YA",
            "а" => "a",
            "б" => "b",
            "в" => "v",
            "г" => "g",
            "д" => "d",
            "е" => "e",
            "ё" => "e",
            "ж" => "j",
            "з" => "z",
            "и" => "i",
            "й" => "y",
            "к" => "k",
            "л" => "l",
            "м" => "m",
            "н" => "n",
            "о" => "o",
            "п" => "p",
            "р" => "r",
            "с" => "s",
            "т" => "t",
            "у" => "u",
            "ф" => "f",
            "х" => "h",
            "ц" => "ts",
            "ч" => "ch",
            "ш" => "sh",
            "щ" => "sch",
            "ъ" => "y",
            "ы" => "yi",
            "ь" => "",
            "э" => "e",
            "ю" => "yu",
            "я" => "ya",
            "«" => "",
            "»" => "",
            "№" => "",
            "Ӏ" => "",
            "’" => "",
            "ˮ" => "",
            "_" => "-",
            "'" => "",
            "`" => "",
            "^" => "",
            "\." => "",
            "," => "",
            ":" => "",
            ";" => "",
            "<" => "",
            ">" => "",
            "!" => "",
            "\(" => "",
            "\)" => "",
            "/" => "",
            "%" => "-",
            "#" => "-",
        ];

        foreach ($tr as $ru => $en) {
            $text = mb_eregi_replace($ru, $en, $text);
        }

        if ($toLower) {
            $text = mb_strtolower($text);
        }

        $text = str_replace(' ', '-', $text);

        return $text;
    }

}
