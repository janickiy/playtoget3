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
            'file.required' => 'Choose a photo to upload.',
            'file.image' => 'Only images can be uploaded.',
            'file.mimes' => 'Only JPG, PNG, or GIF images can be uploaded.',
            'file.extensions' => 'The file extension must be JPG, PNG, or GIF.',
            'file.max' => 'Photo size must not exceed 32 MB.',
            'categorie.required' => 'Choose an album for upload.',
            'categorie.integer' => 'Invalid upload album.',
            'categorie.min' => 'Invalid upload album.',
        ];
    }
}
