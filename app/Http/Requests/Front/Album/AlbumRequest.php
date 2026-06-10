<?php

namespace App\Http\Requests\Front\Album;

use App\DTO\Album\AlbumData;
use Illuminate\Foundation\Http\FormRequest;

class AlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите название альбома.',
        ];
    }

    public function toDto(): AlbumData
    {
        return AlbumData::fromArray($this->validated());
    }
}
