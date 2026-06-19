<?php

namespace App\Http\Requests\Admin\SportTypes;

use App\Models\SportType;
use Illuminate\Validation\Rule;

class EditRequest extends StoreRequest
{
    /**
     * Returns rules validation form editing sport type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = (int) $this->input('id');

        return [
            'id' => ['required', 'integer', 'exists:' . SportType::getTableName() . ',id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(SportType::getTableName(), 'name')->ignore($id),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:' . SportType::getTableName() . ',id',
                Rule::notIn([$id]),
            ],
        ];
    }
}
