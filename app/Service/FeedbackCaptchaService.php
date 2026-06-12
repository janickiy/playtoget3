<?php

namespace App\Service;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;

class FeedbackCaptchaService
{
    private const SESSION_KEY = 'feedback_captcha_code';
    private const CODE_LENGTH = 5;
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Генерирует код CAPTCHA и сохраняет его в сессии пользователя.
     */
    public function generate(): string
    {
        $code = '';
        $maxIndex = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        Session::put(self::SESSION_KEY, $code);

        return $code;
    }

    /**
     * Проверяет введенный пользователем код CAPTCHA с учетом регистра.
     */
    public function isValid(?string $value): bool
    {
        $expected = Session::get(self::SESSION_KEY);

        if (! is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals(strtolower($expected), strtolower(trim((string) $value)));
    }

    /**
     * Удаляет сохраненный код CAPTCHA из сессии после успешной проверки.
     */
    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Формирует PNG-изображение CAPTCHA и обновляет код в сессии.
     */
    public function imageResponse(): Response
    {
        $code = $this->generate();
        $image = imagecreatetruecolor(160, 50);
        $background = imagecolorallocate($image, 238, 242, 244);
        $text = imagecolorallocate($image, 54, 68, 93);
        $noise = imagecolorallocate($image, 169, 188, 199);

        imagefilledrectangle($image, 0, 0, 160, 50, $background);

        for ($i = 0; $i < 8; $i++) {
            imageline(
                $image,
                random_int(0, 160),
                random_int(0, 50),
                random_int(0, 160),
                random_int(0, 50),
                $noise,
            );
        }

        imagestring($image, 5, 42, 17, $code, $text);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return response((string) $contents, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
