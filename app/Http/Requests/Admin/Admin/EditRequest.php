<?php

namespace App\Http\Requests\Admin\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . Admin::getTableName() . ',id'],
            'login' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique(Admin::getTableName(), 'login')->ignore((int) $this->input('id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(array_keys(Admin::$role_name))],
            'password' => ['nullable', 'string', 'min:6'],
            'password_again' => ['nullable', 'string', 'min:6', 'same:password'],
        ];
    }
}
