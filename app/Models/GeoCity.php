<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoCity extends BaseModel
{
    protected $table = 'geo_city';

    protected $fillable = [
        'country_id',
        'region_id',
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
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(GeoRegion::class);
    }

    /**
     * @return HasMany
     */
    public function targets(): HasMany
    {
        return $this->hasMany(GeoTarget::class, 'city_id');
    }
}
