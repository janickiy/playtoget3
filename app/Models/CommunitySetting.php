<?php

namespace App\Models;

class CommunitySetting extends BaseModel
{
    protected $table = 'communities_settings';

    protected $fillable = [
        'permission_wall',
        'permission_photo',
        'permission_video',
        'type',
        'community_id',
    ];

    public function community()
    {
        return $this->belongsTo(Community::class);
    }
}
