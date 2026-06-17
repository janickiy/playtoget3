<?php

namespace App\Http\Requests\Front\Community;

use App\DTO\Community\CommunityData;
use Illuminate\Foundation\Http\FormRequest;

class CommunityRequest extends FormRequest
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
            'id_sport' => ['nullable', 'integer'],
            'place' => ['nullable', 'string', 'max:255'],
            'sport' => ['nullable', 'string', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
            'cover_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
            'community.permission_wall' => ['nullable', 'integer', 'min:0', 'max:3'],
            'community.permission_photo' => ['nullable', 'integer', 'min:0', 'max:2'],
            'community.permission_video' => ['nullable', 'integer', 'min:0', 'max:2'],
            'community.type' => ['nullable', 'integer', 'min:0', 'max:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter the name of ' . $this->entityNameGenitive() . '.',
        ];
    }

    public function toDto(bool $withSettings = false): CommunityData
    {
        return CommunityData::fromArray($this->validated() + [
            'avatar_file' => $this->file('avatar_file'),
            'cover_file' => $this->file('cover_file'),
        ], $withSettings);
    }

    private function entityNameGenitive(): string
    {
        $routeName = (string) $this->route()?->getName();

        return str_starts_with($routeName, 'front.groups.') ? 'group' : 'team';
    }
}
