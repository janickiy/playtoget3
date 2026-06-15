<?php

namespace App\Http\Requests\Front\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class PhotoUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,gif', 'extensions:jpg,jpeg,png,gif', 'max:32768'],
            'categorie' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Выберите фото для загрузки.',
            'file.image' => 'Можно загружать только изображения.',
            'file.mimes' => 'Можно загружать только изображения JPG, PNG или GIF.',
            'file.extensions' => 'Расширение файла должно быть JPG, PNG или GIF.',
            'file.max' => 'Размер фото не должен превышать 32 МБ.',
            'categorie.required' => 'Выберите альбом для загрузки.',
            'categorie.integer' => 'Некорректный альбом для загрузки.',
            'categorie.min' => 'Некорректный альбом для загрузки.',
        ];
    }
}
