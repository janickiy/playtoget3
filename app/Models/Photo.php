<?php

namespace App\Models;

class Photo extends BaseModel
{
    protected $table = 'photos';

    public $timestamps = true;

    protected $fillable = [
        'photoalbum_id',
        'small_photo',
        'photo',
        'description',
        'owner_id',
        'banned',
        'moderate',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'banned' => 'boolean',
            'moderate' => 'boolean',
        ];
    }

    public function album()
    {
        return $this->belongsTo(Photoalbum::class, 'photoalbum_id');
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

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
