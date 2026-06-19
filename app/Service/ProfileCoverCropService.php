<?php

namespace App\Service;

use App\DTO\Profile\ImageCropData;
use App\Models\User;
use App\Support\MediaPath;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileCoverCropService
{
    private const COVER_WIDTH = 1200;
    private const COVER_HEIGHT = 350;

    private ImageFileService $images;

    /**
     * Connects service for working with uploaded images.
     *
     * @param ImageFileService|null $images
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Trims the uploaded profile cover into a temporary file of the required size.
     *
     * @param User $user
     * @param ImageCropData $data
     * @return array
     */
    public function cropTemporaryCover(User $user, ImageCropData $data): array
    {
        $file = $data->file;
        $source = $this->images->imageResource($file);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            throw new RuntimeException('Failed to read the image.');
        }

        $x = max(0, (int) floor($data->x));
        $y = max(0, (int) floor($data->y));
        $width = max(0, (int) floor($data->width));
        $height = max(0, (int) floor($data->height));
        $width = min($width, $sourceWidth - $x);
        $height = min($height, $sourceHeight - $y);

        if ($width < 300 || $height < 80) {
            imagedestroy($source);

            throw new RuntimeException('The selected area is too small.');
        }

        [$x, $y, $width, $height] = $this->normalizeCoverCrop($x, $y, $width, $height);

        if ($width < 300 || $height < 80) {
            imagedestroy($source);

            throw new RuntimeException('The selected area is outside the image bounds.');
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
            throw new RuntimeException('Failed to process the image.');
        }

        $filename = $this->images->temporaryProfileFilename((int) $user->id);
        $path = MediaPath::storage('profile_tmp_cover', $filename);

        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Failed to save the image.');
        }

        return [
            'file' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Normalizes the crop cover area to the target proportions.
     *
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

}
