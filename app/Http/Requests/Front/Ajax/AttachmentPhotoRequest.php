<?php

namespace App\Http\Requests\Front\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Выберите фото для загрузки.',
            'file.image' => 'Можно загружать только изображения.',
            'file.mimes' => 'Можно загружать только изображения JPG или PNG.',
            'file.extensions' => 'Расширение файла должно быть JPG или PNG.',
            'file.max' => 'Размер фото не должен превышать 10 МБ.',
        ];
    }
}
