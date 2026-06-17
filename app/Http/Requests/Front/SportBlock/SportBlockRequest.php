<?php

namespace App\Http\Requests\Front\SportBlock;

use App\DTO\SportBlock\SportBlockData;
use Illuminate\Foundation\Http\FormRequest;

class SportBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'id_place' => ['nullable', 'integer'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter the name of ' . $this->entityNameGenitive() . '.',
        ];
    }

    public function toDto(): SportBlockData
    {
        return SportBlockData::fromArray($this->validated() + [
            'avatar_file' => $this->file('avatar_file'),
        ]);
    }

    private function entityNameGenitive(): string
    {
        $routeName = (string) $this->route()?->getName();

        return match (true) {
            str_starts_with($routeName, 'front.fitness.') => 'fitness',
            str_starts_with($routeName, 'front.shops.') => 'shop',
            default => 'playground',
        };
    }
}
