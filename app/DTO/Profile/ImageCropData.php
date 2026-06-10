<?php

namespace App\DTO\Profile;

use App\DTO\DataTransferObject;
use Illuminate\Http\UploadedFile;

final readonly class ImageCropData implements DataTransferObject
{
    public function __construct(
        public UploadedFile $file,
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
            x: (float) ($data['x'] ?? 0),
            y: (float) ($data['y'] ?? 0),
            width: (float) ($data['w'] ?? 0),
            height: (float) ($data['h'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'w' => $this->width,
            'h' => $this->height,
        ];
    }
}
