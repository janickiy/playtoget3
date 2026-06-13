<?php

namespace App\Http\Requests\Front\Profile;

use App\DTO\Profile\ProfileSettingsData;
use Illuminate\Foundation\Http\FormRequest;

class ProfileSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user.contact_email' => ['nullable', 'email', 'max:100'],
            'user.phone' => ['nullable', 'string', 'max:255'],
            'user.telegram' => ['nullable', 'string', 'max:255'],
            'user.whatsapp' => ['nullable', 'string', 'max:1000'],
            'user.viber' => ['nullable', 'string', 'max:1000'],
            'user.website' => ['nullable', 'string', 'max:255'],
            'user.permission_send_message' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_profile' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_friends' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_photo' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_video' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_wall' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_comment_photo' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_comment_video' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_comment_wall' => ['nullable', 'integer', 'in:0,1,2'],
            'user.notification_friends_request' => ['nullable', 'in:yes'],
            'user.notification_private_messages' => ['nullable', 'in:yes'],
            'user.notification_wall_comments' => ['nullable', 'in:yes'],
            'user.notification_picture_comments' => ['nullable', 'in:yes'],
            'user.notification_video_comments' => ['nullable', 'in:yes'],
            'user.notification_answers_in_comments' => ['nullable', 'in:yes'],
            'user.notification_events' => ['nullable', 'in:yes'],
            'user.notification_birthdays' => ['nullable', 'in:yes'],
            'file_ava' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'file_cover' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function toDto(): ProfileSettingsData
    {
        return ProfileSettingsData::fromArray($this->validated() + [
            'cover' => $this->file('cover'),
        ]);
    }
}
