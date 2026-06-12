<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Settings;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key_cd' => 'required|max:255|unique:' . Settings::getTableName() . ',key_cd',
            'name' => 'nullable|max:255',
            'type' => 'required|string|max:255',
            'value' => 'required',
            'display_value' => 'nullable|max:255',
        ];
    }
}
