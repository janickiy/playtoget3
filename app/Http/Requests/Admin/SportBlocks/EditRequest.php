<?php

namespace App\Http\Requests\Admin\SportBlocks;

use App\Models\SportBlock;

class EditRequest extends StoreRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required', 'integer', 'exists:' . SportBlock::getTableName() . ',id'],
        ]);
    }
}
