<?php

namespace App\Models;

class Comment extends BaseModel
{
    protected $table = 'comments';

    public $timestamps = true;

    protected $fillable = [
        'commentable_type',
        'content_id',
        'user_id',
        'behalfable_type',
        'behalf_id',
        'content',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo(__FUNCTION__, 'commentable_type', 'content_id');
    }

    public function behalfable()
    {
        return $this->morphTo(__FUNCTION__, 'behalfable_type', 'behalf_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable', 'type', 'content_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'content_id');
    }

    public function shares()
    {
        return $this->morphMany(Share::class, 'shareable', 'shareable_type', 'content_id');
    }
}
