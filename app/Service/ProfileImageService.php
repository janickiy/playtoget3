<?php

namespace App\Service;

use App\DTO\Profile\ImageCropData;
use App\Models\User;
use App\Support\MediaPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileImageService
{
    private ImageFileService $images;

    /**
     * Connects сервис для работы с uploaded images.
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Обрезает uploaded avatar во temporary квадратный файл.
     *
     * @param User $user
     * @param ImageCropData $data
     * @return array
     */
    public function cropTemporaryAvatar(User $user, ImageCropData $data): array
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
        $size = min($width, $height);

        if ($size < 100) {
            imagedestroy($source);

            throw new RuntimeException('The selected area is too small.');
        }

        $size = min($size, $sourceWidth - $x, $sourceHeight - $y);

        if ($size < 100) {
            imagedestroy($source);

            throw new RuntimeException('The selected area is outside the image bounds.');
        }

        $target = imagecreatetruecolor(300, 300);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, $x, $y, 300, 300, $size, $size);
        imagedestroy($source);

        ob_start();
        imagejpeg($target, null, 90);
        $contents = ob_get_clean();
        imagedestroy($target);

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException('Failed to process the image.');
        }

        $filename = $this->images->temporaryProfileFilename((int) $user->id);
        $path = MediaPath::storage('profile_tmp_avatar', $filename);

        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Failed to save the image.');
        }

        return [
            'file' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Сохраняет image профиля в указанную директорию.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $userId
     * @return string
     */
    public function storeUserImage(UploadedFile $file, string $directory, int $userId): string
    {
        $filename = $this->images->userScopedFilename($file, $userId);
        $path = MediaPath::fromRelative($directory, $filename);
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || ! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Failed to save the profile image.');
        }

        return $filename;
    }

    /**
     * Переносит temporary avatar в permanent storage user.
     *
     * @param string|null $temporaryAvatar
     * @param int $userId
     * @return string|null
     */
    public function promoteTemporaryAvatar(?string $temporaryAvatar, int $userId): ?string
    {
        if (! $temporaryAvatar) {
            return null;
        }

        return $this->promoteTemporaryImage(
            $temporaryAvatar,
            $userId,
            MediaPath::storage('profile_tmp_avatar'),
            MediaPath::storage('user_avatar'),
            'Invalid avatar filename.',
            'Avatar file not found.',
            'Failed to save avatar.',
        );
    }

    /**
     * Переносит temporary cover в permanent storage user.
     *
     * @param string|null $temporaryCover
     * @param int $userId
     * @return string|null
     */
    public function promoteTemporaryCover(?string $temporaryCover, int $userId): ?string
    {
        if (! $temporaryCover) {
            return null;
        }

        return $this->promoteTemporaryImage(
            $temporaryCover,
            $userId,
            MediaPath::storage('profile_tmp_cover'),
            MediaPath::storage('user_cover'),
            'Invalid cover filename.',
            'Cover file not found.',
            'Failed to save cover.',
        );
    }

    /**
     * Deletes image профиля из указанной директории.
     *
     * @param string $directory
     * @param string|null $filename
     * @return void
     */
    public function deleteUserImage(string $directory, ?string $filename): void
    {
        if (! $filename) {
            return;
        }

        Storage::disk('public')->delete(MediaPath::fromRelative($directory, $filename));
    }

    /**
     * Переносит временное image в permanent директорию профиля.
     *
     * @param string $temporaryImage
     * @param int $userId
     * @param string $sourceDirectory
     * @param string $targetDirectory
     * @param string $invalidNameMessage
     * @param string $missingFileMessage
     * @param string $copyFailedMessage
     * @return string
     */
    private function promoteTemporaryImage(
        string $temporaryImage,
        int $userId,
        string $sourceDirectory,
        string $targetDirectory,
        string $invalidNameMessage,
        string $missingFileMessage,
        string $copyFailedMessage,
    ): string {
        $filename = basename($temporaryImage);

        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $filename)) {
            throw new RuntimeException($invalidNameMessage);
        }

        $disk = Storage::disk('public');
        $source = rtrim($sourceDirectory, '/') . '/' . $filename;

        if (! $disk->exists($source)) {
            throw new RuntimeException($missingFileMessage);
        }

        $targetFilename = sprintf('%d_%s', $userId, preg_replace('/^\d+_/', '', $filename));
        $target = rtrim($targetDirectory, '/') . '/' . $targetFilename;

        if (! $disk->copy($source, $target)) {
            throw new RuntimeException($copyFailedMessage);
        }

        $disk->delete($source);

        return $targetFilename;
    }

}
