<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
{
    /**
     * Разрешает изменение статуса пользователя авторизованному администратору.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Подмешивает id из маршрута в данные запроса для общей валидации.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Возвращает правила проверки пользователя, чей статус меняется.
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
