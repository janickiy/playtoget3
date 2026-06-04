<?php

namespace App\Models;

class UserActivity extends BaseModel
{
    protected $table = 'user_activity';

    protected $fillable = [
        'user_id',
        'last_activity',
    ];

    protected function casts(): array
    {
        return [
            'last_activity' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
