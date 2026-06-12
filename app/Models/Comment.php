<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends BaseModel
{
    use StaticTableName;

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

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'commentable_type', 'content_id');
    }

    /**
     * @return MorphTo
     */
    public function behalfable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'behalfable_type', 'behalf_id');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable', 'type', 'content_id');
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
}
