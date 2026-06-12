<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    /**
     * @return BelongsTo
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(PhotoAlbums::class, 'photoalbum_id');
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
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
