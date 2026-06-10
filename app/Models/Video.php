<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'banned' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(VideoAlbums::class, 'videoalbum_id');
    }

    /**
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'content_id');
    }

    /**
     * @return MorphMany
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'content_id');
    }

    /**
     * @return MorphMany
     */
    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable', 'shareable_type', 'content_id');
    }

    /**
     * @return HasMany
     */
    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }
}
