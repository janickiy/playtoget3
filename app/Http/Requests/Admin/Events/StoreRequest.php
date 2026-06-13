<?php

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
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
            'name' => ['required', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'description' => ['nullable', 'string'],
            'sport_type' => ['nullable', 'string', 'max:255'],
            'cover_page' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'integer', Rule::in(array_keys(EventStatus::options()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'название',
            'date_from' => 'дата начала',
            'date_to' => 'дата окончания',
            'description' => 'описание',
            'sport_type' => 'вид спорта',
            'cover_page' => 'обложка',
            'place' => 'место',
            'address' => 'адрес',
            'status' => 'статус',
        ];
    }
}
