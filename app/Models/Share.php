<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
    public function shareable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'shareable_type', 'content_id');
    }
}
