<?php

namespace App\Http\Requests\Front\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember_me' => ['nullable', 'boolean'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('username');
    }

    public function password(): string
    {
        return (string) $this->input('password');
    }

    public function remember(): bool
    {
        return $this->boolean('remember_me');
    }
}
