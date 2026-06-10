<?php

namespace App\Http\Requests\Front\Video;

use App\DTO\Video\VideoData;
use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'videoalbum_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'video.required' => 'Укажите ссылку на видео.',
            'videoalbum_id.required' => 'Выберите альбом.',
        ];
    }

    public function toDto(): VideoData
    {
        return VideoData::fromArray($this->validated());
    }
}
