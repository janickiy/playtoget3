<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportBlock extends Model
{
    use StaticTableName;

    protected $fillable = [
        'name',
        'about',
        'place',
        'address',
        'phone',
        'email',
        'avatar',
        'website',
        'type',
        'owner_id',
        'active',
        'banned',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'banned' => 'boolean',
        ];
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
    public function photoalbums(): HasMany
    {
        return $this->hasMany(PhotoAlbums::class, 'owner_id')->where('photoalbumable_type', $this->type);
    }

    /**
     * @return HasMany
     */
    public function geoTargets(): HasMany
    {
        return $this->hasMany(GeoTarget::class, 'target_id')->where('target_type', $this->type);
    }
}
