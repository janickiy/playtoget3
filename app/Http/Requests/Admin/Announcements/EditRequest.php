<?php

namespace App\Http\Requests\Admin\Announcements;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила валидации формы редактирования объявления.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . Announcement::getTableName() . ',id'],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique(Announcement::getTableName(), 'slug')->ignore((int) $this->input('id')),
            ],
            'published' => ['nullable', 'boolean'],
        ];
    }
}
