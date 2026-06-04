<?php

namespace App\Models;

class Like extends BaseModel
{
    protected $table = 'likes';

    protected $fillable = [
        'user_id',
        'likeable_type',
        'content_id',
        'time',
    ];

    protected function casts(): array
    {
        return [
            'time' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likeable()
    {
        return $this->morphTo(__FUNCTION__, 'likeable_type', 'content_id');
    }
}
