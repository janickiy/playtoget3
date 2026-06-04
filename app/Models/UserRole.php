<?php

namespace App\Models;

class UserRole extends BaseModel
{
    protected $table = 'users_roles';

    protected $fillable = [
        'user_id',
        'role_id',
        'descr',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
