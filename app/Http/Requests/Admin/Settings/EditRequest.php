<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Settings;
use Illuminate\Foundation\Http\FormRequest;

class EditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:' . Settings::getTableName() . ',id',
            'key_cd' => 'required|max:255|unique:' . Settings::getTableName() . ',key_cd,' . $this->id,
            'name' => 'nullable|max:255',
            'type' => 'required|string|max:255',
            'value' => strtoupper((string) $this->input('type')) === 'FILE' ? 'nullable' : 'required',
            'display_value' => 'nullable|max:255',
        ];
    }
}
