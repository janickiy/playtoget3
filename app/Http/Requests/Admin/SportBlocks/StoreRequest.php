<?php

namespace App\Http\Requests\Admin\SportBlocks;

use App\Enums\SportBlockStatus;
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
            'type' => ['required', 'string', Rule::in(['playground', 'shop', 'fitness'])],
            'name' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'integer', Rule::in(array_keys(SportBlockStatus::options()))],
            'recommended' => ['required', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'type',
            'name' => 'name',
            'about' => 'description',
            'place' => 'place',
            'address' => 'address',
            'phone' => 'phone',
            'email' => 'email',
            'avatar' => 'avatar',
            'website' => 'website',
            'owner_id' => 'owner',
            'status' => 'status',
            'recommended' => 'recommended',
        ];
    }
}
