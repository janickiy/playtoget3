<?php

namespace App\Service;

use App\DTO\Profile\ImageCropData;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileImageService
{
    private ImageFileService $images;

    /**
     * Подключает сервис для работы с загруженными изображениями.
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Обрезает загруженный аватар во временный квадратный файл.
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

            throw new RuntimeException('Не удалось прочитать изображение.');
        }

        $x = max(0, (int) floor($data->x));
        $y = max(0, (int) floor($data->y));
        $width = max(0, (int) floor($data->width));
        $height = max(0, (int) floor($data->height));
        $size = min($width, $height);

        if ($size < 100) {
            imagedestroy($source);

            throw new RuntimeException('Выделенная область слишком мала.');
        }

        $size = min($size, $sourceWidth - $x, $sourceHeight - $y);

        if ($size < 100) {
            imagedestroy($source);

            throw new RuntimeException('Выделенная область выходит за границы изображения.');
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
            throw new RuntimeException('Не удалось обработать изображение.');
        }

        $filename = $this->images->temporaryProfileFilename((int) $user->id);
        $path = 'images/tmp/profile/avatar/' . $filename;

        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Не удалось сохранить изображение.');
        }

        return [
            'file' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Сохраняет изображение профиля в указанную директорию.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $userId
     * @return string
     */
    public function storeUserImage(UploadedFile $file, string $directory, int $userId): string
    {
        $filename = $this->images->userScopedFilename($file, $userId);
        $path = 'images/' . trim($directory, '/') . '/' . $filename;
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || ! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Не удалось сохранить изображение профиля.');
        }

        return $filename;
    }

    /**
     * Переносит временный аватар в постоянное хранилище пользователя.
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
            'images/tmp/profile/avatar/',
            'images/user/avatar/',
            'Некорректное имя файла аватара.',
            'Файл аватара не найден.',
            'Не удалось сохранить аватар.',
        );
    }

    /**
     * Переносит временную обложку в постоянное хранилище пользователя.
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
            'images/tmp/profile/cover_page/',
            'images/user/cover_page/',
            'Некорректное имя файла обложки.',
            'Файл обложки не найден.',
            'Не удалось сохранить обложку.',
        );
    }

    /**
     * Удаляет изображение профиля из указанной директории.
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

        Storage::disk('public')->delete('images/' . trim($directory, '/') . '/' . $filename);
    }

    /**
     * Переносит временное изображение в постоянную директорию профиля.
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
        $source = $sourceDirectory . $filename;

        if (! $disk->exists($source)) {
            throw new RuntimeException($missingFileMessage);
        }

        $targetFilename = sprintf('%d_%s', $userId, preg_replace('/^\d+_/', '', $filename));
        $target = $targetDirectory . $targetFilename;

        if (! $disk->copy($source, $target)) {
            throw new RuntimeException($copyFailedMessage);
        }

        $disk->delete($source);

        return $targetFilename;
    }

}
