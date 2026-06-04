<?php

namespace App\Models;

class Friend extends BaseModel
{
    protected $table = 'friends';

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'added',
    ];

    protected function casts(): array
    {
        return [
            'added' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}
