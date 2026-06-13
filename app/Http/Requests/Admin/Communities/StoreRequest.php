<?php

namespace App\Http\Requests\Admin\Communities;

use App\Enums\CommunityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['team', 'group'])],
            'name' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'cover_page' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:100'],
            'sport_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', Rule::in(array_keys(CommunityStatus::options()))],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'тип',
            'name' => 'название',
            'about' => 'описание',
            'avatar' => 'аватар',
            'cover_page' => 'обложка',
            'place' => 'место',
            'sport_type' => 'вид спорта',
            'status' => 'статус',
        ];
    }
}
