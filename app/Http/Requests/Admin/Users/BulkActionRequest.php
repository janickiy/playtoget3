<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    /**
     * Allows mass actions over users authenticated administrator.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Returns rules validation of mass actions and selected users.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['block', 'unblock', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:' . User::getTableName() . ',id'],
        ];
    }
}
