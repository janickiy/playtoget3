<?php

namespace App\Http\Requests\Admin\Announcements;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила проверки удаления объявления.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . Announcement::getTableName() . ',id'],
        ];
    }
}
