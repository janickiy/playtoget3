<?php

namespace App\Models;

class VideoView extends BaseModel
{
    protected $table = 'video_views';

    protected $fillable = [
        'user_id',
        'video_id',
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

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
