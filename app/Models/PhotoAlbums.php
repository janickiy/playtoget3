<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PhotoAlbums extends Model
{
    use StaticTableName;

    protected $table = 'photoalbums';

    protected $fillable = [
        'name',
        'photoalbumable_type',
        'owner_id',
    ];

    /**
     * @return MorphTo
     */
    public function photoalbumable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'photoalbumable_type', 'owner_id');
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
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
