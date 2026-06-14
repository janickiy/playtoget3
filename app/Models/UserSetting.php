<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends BaseModel
{
    use StaticTableName;

    protected $table = 'usersettings';

    protected $fillable = [
        'permission_send_message',
        'permission_view_profile',
        'permission_view_friends',
        'permission_view_photo',
        'permission_view_video',
        'permission_view_wall',
        'permission_comment_photo',
        'permission_comment_video',
        'permission_comment_wall',
        'notification_friends_request',
        'notification_private_messages',
        'notification_wall_comments',
        'notification_picture_comments',
        'notification_video_comments',
        'notification_answers_in_comments',
        'notification_events',
        'notification_birthdays',
        'user_id',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
