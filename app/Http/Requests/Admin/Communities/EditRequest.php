<?php

namespace App\Http\Requests\Admin\Communities;

use App\Models\Community;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends StoreRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required', 'integer', 'exists:' . Community::getTableName() . ',id'],
            'type' => ['required', 'string', Rule::in(['team', 'group'])],
        ]);
    }
}
