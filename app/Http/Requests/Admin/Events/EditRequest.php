<?php

namespace App\Http\Requests\Admin\Events;

use App\Models\Event;

class EditRequest extends StoreRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required', 'integer', 'exists:' . Event::getTableName() . ',id'],
        ]);
    }
}
