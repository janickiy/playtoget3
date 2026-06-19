<?php

namespace App\Http\Requests\Admin\SportTypes;

use App\Models\SportType;
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
     * Returns rules validation deletion sport type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . SportType::getTableName() . ',id'],
        ];
    }
}
