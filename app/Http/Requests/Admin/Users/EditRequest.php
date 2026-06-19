<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    /**
     * Allows an authenticated administrator to process the user edit form.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Returns rules validation form editing user.
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
            'nickname' => ['nullable', 'string', 'max:255'],
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
            'status' => ['required', 'integer', Rule::in(array_keys(UserStatus::options()))],
            'confirmed_at' => ['nullable', 'date'],
        ];
    }
}
