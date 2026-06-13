<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    /**
     * Разрешает обработку формы редактирования пользователя авторизованному администратору.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила валидации формы редактирования пользователя.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . User::getTableName() . ',id'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::getTableName(), 'email')->ignore((int) $this->input('id')),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'password_again' => ['nullable', 'string', 'min:6', 'same:password'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'secondname' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', Rule::in(['male', 'female'])],
            'birthday' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'viber' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'about_sport' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:16'],
            'confirmed' => ['nullable', 'boolean'],
            'banned' => ['nullable', 'boolean'],
            'deleted' => ['nullable', 'boolean'],
        ];
    }
}
