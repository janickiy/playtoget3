<?php

namespace App\Http\Requests\Front\Feedback;

use App\DTO\Feedback\FeedbackData;
use App\Service\FeedbackCaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'captcha' => ['required', 'string'],
        ];
    }

    /**
     * Подключает проверку серверного кода CAPTCHA после базовой валидации.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $captcha = trim((string) $this->input('captcha'));

                if ($captcha === '') {
                    return;
                }

                if (! app(FeedbackCaptchaService::class)->isValid($captcha)) {
                    $validator->errors()->add('captcha', 'Проверочный код указан неверно.');
                }
            },
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
            'captcha.required' => 'Введите проверочный код.',
        ];
    }

    public function toDto(): FeedbackData
    {
        return FeedbackData::fromArray($this->validated());
    }
}
