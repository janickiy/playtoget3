<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunitySetting extends BaseModel
{
    use StaticTableName;

    protected $table = 'communities_settings';

    protected $fillable = [
        'permission_wall',
        'permission_photo',
        'permission_video',
        'type',
        'community_id',
    ];

    protected $casts = [
        'permission_wall' => 'integer',
        'permission_photo' => 'integer',
        'permission_video' => 'integer',
        'type' => 'integer',
        'community_id' => 'integer',
    ];

    /**
     * @return BelongsTo
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
