<?php

namespace App\Http\Requests\Admin\Pages;

use App\Models\Content;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique(Content::getTableName(), 'slug')],
            'published' => ['nullable', 'boolean'],
        ];
    }
}
