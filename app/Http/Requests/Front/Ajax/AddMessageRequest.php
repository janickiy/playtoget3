<?php

namespace App\Http\Requests\Front\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class AddMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attach' => ['nullable'],
        ];
    }
}
