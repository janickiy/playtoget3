<?php

namespace App\Http\Requests\Admin\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'login' => ['required', 'string', 'min:3', 'max:255', Rule::unique(Admin::getTableName(), 'login')],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys(Admin::$role_name))],
            'password' => ['required', 'string', 'min:6'],
            'password_again' => ['required', 'string', 'min:6', 'same:password'],
        ];
    }
}
