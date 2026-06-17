<?php

namespace App\Service;

class VideoService
{

    /**
     * Builds HTML-плеер для поддерживаемого video.
     *
     * @param string $provider
     * @param string $video
     * @return string
     */
    public function playerHtml(string $provider, string $video): string
    {
        if ($provider === 'youtube' && $video !== '') {
            return '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . e($video) . '" frameborder="0" allowfullscreen></iframe>';
        }

        return '';
    }

    /**
     * Detects provider и идентификатор video по ссылке.
     *
     * @param string $link
     * @return array|null
     */
    public function detectVideo(string $link): ?array
    {
        $link = trim($link);

        if ($link === '') {
            return null;
        }

        if (preg_match('~youtu\.be/([^\?&/]+)~i', $link, $matches)) {
            return ['provider' => 'youtube', 'video' => $matches[1]];
        }

        if (preg_match('~(?:v/|embed/|watch\?(?:.*&)?v=)([^&\?]+)~i', $link, $matches)) {
            return ['provider' => 'youtube', 'video' => $matches[1]];
        }

        return null;
    }
}
