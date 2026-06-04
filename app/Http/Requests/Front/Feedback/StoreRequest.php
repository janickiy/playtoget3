<?php

namespace App\Http\Requests\Front\Feedback;

use App\DTO\Feedback\FeedbackData;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string'],
        ];
    }

    public function toDto(): FeedbackData
    {
        return new FeedbackData(
            $this->input('subject'),
            (string) $this->input('name'),
            (string) $this->input('email'),
            (string) $this->input('message'),
        );
    }
}
