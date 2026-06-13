<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    /**
     * Разрешает массовые действия над пользователями авторизованному администратору.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила проверки массового действия и выбранных пользователей.
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
