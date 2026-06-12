<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends BaseModel
{
    use StaticTableName;

    protected $table = 'attachment';

    protected $fillable = [
        'type',
        'content_id',
        'photo_id',
    ];

    /**
     * @return MorphTo
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'type', 'content_id');
    }

    /**
     * @return BelongsTo
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }
}
