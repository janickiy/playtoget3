<?php

namespace App\Http\Requests\Admin\SportTypes;

use App\Models\SportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Returns rules validation form adding sport type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(SportType::getTableName(), 'name')],
            'parent_id' => ['nullable', 'integer', 'exists:' . SportType::getTableName() . ',id'],
        ];
    }

    /**
     * Returns localized attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('admin.fields.name'),
            'parent_id' => __('admin.fields.parent'),
        ];
    }
}
