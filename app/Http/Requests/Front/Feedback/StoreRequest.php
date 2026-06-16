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
            'subject.required' => 'Укажите тему сообщения.',
            'name.required' => 'Укажите ваше имя.',
            'email.required' => 'Укажите адрес электронной почты.',
            'email.email' => 'Введите корректный адрес электронной почты.',
            'message.required' => 'Введите сообщение.',
            'g-recaptcha-response.required' => 'Подтвердите, что вы не робот.',
            'g-recaptcha-response.recaptchav3' => 'Проверка reCAPTCHA не пройдена. Попробуйте отправить форму еще раз.',
        ];
    }

    public function toDto(): FeedbackData
    {
        return FeedbackData::fromArray($this->validated());
    }
}
