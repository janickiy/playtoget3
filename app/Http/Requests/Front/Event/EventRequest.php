<?php

namespace App\Http\Requests\Front\Event;

use App\DTO\Event\EventData;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'id_place' => ['nullable', 'integer'],
            'sport' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cover_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter the event name.',
        ];
    }

    public function toDto(): EventData
    {
        return EventData::fromArray($this->validated() + [
            'cover_file' => $this->file('cover_file'),
        ]);
    }
}
