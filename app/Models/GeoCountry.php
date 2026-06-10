<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoCountry extends BaseModel
{
    protected $table = 'geo_country';

    protected $fillable = [
        'name_ru',
        'name_en',
        'code',
        'sort',
    ];

    /**
     * @return HasMany
     */
    public function regions(): HasMany
    {
        return $this->hasMany(GeoRegion::class, 'country_id');
    }

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(GeoCity::class, 'country_id');
    }

    /**
     * @return HasMany
     */
    public function targets(): HasMany
    {
        return $this->hasMany(GeoTarget::class, 'country_id');
    }
}
