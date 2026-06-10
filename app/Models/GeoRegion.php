<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoRegion extends BaseModel
{
    protected $table = 'geo_region';

    protected $fillable = [
        'country_id',
        'name_ru',
        'name_en',
        'sort',
    ];

    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(GeoCountry::class);
    }

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(GeoCity::class, 'region_id');
    }

    /**
     * @return HasMany
     */
    public function targets(): HasMany
    {
        return $this->hasMany(GeoTarget::class, 'region_id');
    }
}
