<?php

namespace App\Http\Requests\Admin\Feedback;

use App\Enums\FeedbackStatus;
use App\Models\Feedback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Returns rules validation editing requests.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:' . Feedback::getTableName() . ',id'],
            'status' => ['required', 'integer', Rule::in(array_keys(FeedbackStatus::options()))],
            'answer' => [
                Rule::requiredIf((int) $this->input('status') === FeedbackStatus::Closed->value),
                'nullable',
                'string',
            ],
        ];
    }
}
