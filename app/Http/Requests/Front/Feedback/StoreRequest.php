<?php

namespace App\Http\Requests\Front\Feedback;

use App\DTO\Feedback\FeedbackData;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string'],
            'g-recaptcha-response' => ['required', 'recaptchav3:feedback,0.5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Enter the message subject.',
            'name.required' => 'Enter your name.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'message.required' => 'Enter message.',
            'g-recaptcha-response.required' => 'Please confirm that you are not a robot.',
            'g-recaptcha-response.recaptchav3' => 'reCAPTCHA verification failed. Please try submitting the form again.',
        ];
    }

    public function toDto(): FeedbackData
    {
        return FeedbackData::fromArray($this->validated());
    }
}
