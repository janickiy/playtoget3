<?php

namespace App\Http\Requests\Front\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class CropCoverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'x' => ['required', 'numeric', 'min:0'],
            'y' => ['required', 'numeric', 'min:0'],
            'w' => ['required', 'numeric', 'min:300'],
            'h' => ['required', 'numeric', 'min:80'],
        ];
    }
}
