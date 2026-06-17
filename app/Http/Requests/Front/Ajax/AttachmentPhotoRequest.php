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
            'file.required' => 'Choose a photo to upload.',
            'file.image' => 'Only images can be uploaded.',
            'file.mimes' => 'Only JPG or PNG images can be uploaded.',
            'file.extensions' => 'The file extension must be JPG or PNG.',
            'file.max' => 'Photo size must not exceed 10 MB.',
        ];
    }
}
