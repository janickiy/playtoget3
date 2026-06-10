<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VideoAlbums extends Model
{
    use StaticTableName;

    protected $table = 'videoalbums';

    protected $fillable = [
        'name',
        'videoalbumable_type',
        'owner_id',
    ];

    /**
     * @return MorphTo
     */
    public function videoalbumable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'videoalbumable_type', 'owner_id');
    }

    /**
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
