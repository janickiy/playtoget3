<?php

namespace App\Http\Requests\Front\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'commentable_type' => ['nullable', 'string', 'in:user,photo,video,team,group,event'],
            'content_id' => ['required', 'integer', 'min:1'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'attach' => ['nullable'],
            'author_community' => ['nullable', 'boolean'],
        ];
    }
}
