<?php

namespace App\Service;

class VideoService
{
    private const PROVIDER_YOUTUBE = 'youtube';

    private const PROVIDER_VIMEO = 'vimeo';

    /**
     * Builds HTML player for supported video.
     *
     * @param string $provider
     * @param string $video
     * @return string
     */
    public function playerHtml(string $provider, string $video): string
    {
        if ($provider === self::PROVIDER_YOUTUBE && $video !== '') {
            return '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . e($video) . '" frameborder="0" allowfullscreen></iframe>';
        }

        if ($provider === self::PROVIDER_VIMEO && $video !== '') {
            return '<iframe width="100%" height="100%" src="https://player.vimeo.com/video/' . e($video) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        }

        return '';
    }

    /**
     * Detects provider and video identifier via link.
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

        if ($video = $this->detectYoutube($link)) {
            return ['provider' => self::PROVIDER_YOUTUBE, 'video' => $video];
        }

        if ($video = $this->detectVimeo($link)) {
            return ['provider' => self::PROVIDER_VIMEO, 'video' => $video];
        }

        return null;
    }

    /**
     * Detects YouTube video identifier from supported link formats.
     *
     * @param string $link
     * @return string|null
     */
    private function detectYoutube(string $link): ?string
    {
        if (preg_match('~youtu\.be/([^\?&/]+)~i', $link, $matches)) {
            return $matches[1];
        }

        if (preg_match('~(?:v/|embed/|watch\?(?:.*&)?v=)([^&\?]+)~i', $link, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Detects Vimeo video identifier from supported link formats.
     *
     * @param string $link
     * @return string|null
     */
    private function detectVimeo(string $link): ?string
    {
        $normalizedLink = preg_match('~^https?://~i', $link) ? $link : 'https://' . $link;
        $host = parse_url($normalizedLink, PHP_URL_HOST);

        if (! is_string($host) || ! preg_match('~(^|\.)vimeo\.com$~i', $host)) {
            return null;
        }

        $path = parse_url($normalizedLink, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        if (preg_match('~/video/(\d+)(?:$|/)~i', $path, $matches)) {
            return $matches[1];
        }

        if (preg_match('~/(?:channels/[^/]+|groups/[^/]+/videos|album/\d+/video|showcase/\d+/video)/(\d+)(?:$|/)~i', $path, $matches)) {
            return $matches[1];
        }

        if (preg_match('~/(\d+)(?:$|/)~', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
