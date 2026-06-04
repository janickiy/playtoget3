<?php

namespace App\Models;

class CommunityRole extends BaseModel
{
    protected $table = 'community_roles';

    protected $fillable = [
        'user_id',
        'community_id',
        'role',
        'role_description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }
}
