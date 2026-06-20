<?php

namespace App\Http\Requests\Front\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:7', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:7'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function token(): string
    {
        return (string) $this->input('token');
    }

    public function password(): string
    {
        return (string) $this->input('password');
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
