<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\Catalog;
use Illuminate\Foundation\Http\FormRequest;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . Catalog::getTableName() . ',id'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
