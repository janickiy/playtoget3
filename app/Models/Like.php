<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends BaseModel
{
    use StaticTableName;

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
    public function likeable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'likeable_type', 'content_id');
    }
}
