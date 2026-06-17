<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
{
    /**
     * Allows изменение statusа user authenticated administratorу.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Merges id из маршрута в data запроса для общей validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Returns rules validation user, чей status меняется.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . User::getTableName() . ',id'],
        ];
    }
}
