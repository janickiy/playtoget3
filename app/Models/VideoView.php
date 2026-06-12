<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoView extends BaseModel
{
    use StaticTableName;

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

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
