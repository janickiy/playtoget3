<?php

namespace App\Models;

class Video extends BaseModel
{
    protected $table = 'videos';

    public $timestamps = true;

    protected $fillable = [
        'videoalbum_id',
        'provider',
        'video',
        'description',
        'owner_id',
        'banned',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'banned' => 'boolean',
        ];
    }

    public function album()
    {
        return $this->belongsTo(Videoalbum::class, 'videoalbum_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'content_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'content_id');
    }

    public function shares()
    {
        return $this->morphMany(Share::class, 'shareable', 'shareable_type', 'content_id');
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }
}
