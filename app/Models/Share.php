<?php

namespace App\Models;

class Share extends BaseModel
{
    protected $table = 'share';

    protected $fillable = [
        'user_id',
        'shareable_type',
        'time',
        'content_id',
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

    public function shareable()
    {
        return $this->morphTo(__FUNCTION__, 'shareable_type', 'content_id');
    }
}
