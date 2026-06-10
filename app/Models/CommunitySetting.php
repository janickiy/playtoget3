<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunitySetting extends BaseModel
{
    protected $table = 'communities_settings';

    protected $fillable = [
        'permission_wall',
        'permission_photo',
        'permission_video',
        'type',
        'community_id',
    ];

    /**
     * @return BelongsTo
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
