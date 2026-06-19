<?php

namespace App\Http\Requests\Admin\SportLevels;

use App\Models\SportLevel;
use Illuminate\Validation\Rule;

class EditRequest extends StoreRequest
{
    /**
     * Returns rules validation form editing sport level.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = (int) $this->input('id');

        return [
            'id' => ['required', 'integer', 'exists:' . SportLevel::getTableName() . ',id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(SportLevel::getTableName(), 'name')->ignore($id),
            ],
        ];
    }
}
