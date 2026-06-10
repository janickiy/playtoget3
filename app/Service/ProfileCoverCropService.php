<?php

namespace App\Service;

use App\DTO\Profile\ImageCropData;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProfileCoverCropService
{
    private const COVER_WIDTH = 1200;
    private const COVER_HEIGHT = 350;

    /**
     * @param User $user
     * @param ImageCropData $data
     * @return array
     */
    public function cropTemporaryCover(User $user, ImageCropData $data): array
    {
        $file = $data->file;
        $source = $this->imageResource($file);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            throw new RuntimeException('Не удалось прочитать изображение.');
        }

        $x = max(0, (int) floor($data->x));
        $y = max(0, (int) floor($data->y));
        $width = max(0, (int) floor($data->width));
        $height = max(0, (int) floor($data->height));
        $width = min($width, $sourceWidth - $x);
        $height = min($height, $sourceHeight - $y);

        if ($width < 300 || $height < 80) {
            imagedestroy($source);

            throw new RuntimeException('Выделенная область слишком мала.');
        }

        [$x, $y, $width, $height] = $this->normalizeCoverCrop($x, $y, $width, $height);

        if ($width < 300 || $height < 80) {
            imagedestroy($source);

            throw new RuntimeException('Выделенная область выходит за границы изображения.');
        }

        $target = imagecreatetruecolor(self::COVER_WIDTH, self::COVER_HEIGHT);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $x,
            $y,
            self::COVER_WIDTH,
            self::COVER_HEIGHT,
            $width,
            $height,
        );
        imagedestroy($source);

        ob_start();
        imagejpeg($target, null, 90);
        $contents = ob_get_clean();
        imagedestroy($target);

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException('Не удалось обработать изображение.');
        }

        $filename = sprintf('%d_%s.jpg', $user->id, Str::lower(Str::random(32)));
        $path = 'images/tmp/profile/cover_page/' . $filename;

        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Не удалось сохранить изображение.');
        }

        return [
            'file' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return int[]
     */
    private function normalizeCoverCrop(int $x, int $y, int $width, int $height): array
    {
        $targetRatio = self::COVER_WIDTH / self::COVER_HEIGHT;
        $currentRatio = $width / max(1, $height);

        if (abs($currentRatio - $targetRatio) < 0.01) {
            return [$x, $y, $width, $height];
        }

        if ($currentRatio > $targetRatio) {
            $normalizedWidth = (int) floor($height * $targetRatio);
            $x += (int) floor(($width - $normalizedWidth) / 2);
            $width = $normalizedWidth;
        } else {
            $normalizedHeight = (int) floor($width / $targetRatio);
            $y += (int) floor(($height - $normalizedHeight) / 2);
            $height = $normalizedHeight;
        }

        return [$x, $y, $width, $height];
    }

    /**
     * @param UploadedFile $file
     * @return GdImage
     */
    private function imageResource(UploadedFile $file): GdImage
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        return $mime === 'image/jpeg' || $mime === 'image/jpg'
            ? $this->orientJpeg($image, $path)
            : $image;
    }

    /**
     * @param GdImage $image
     * @param string $path
     * @return GdImage
     */
    private function orientJpeg(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 0) : 0;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
