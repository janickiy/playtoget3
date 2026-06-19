<?php

namespace App\Http\Requests\Admin\SportLevels;

use App\Models\SportLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Returns rules validation form adding sport level.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(SportLevel::getTableName(), 'name')],
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
        ];
    }
}
