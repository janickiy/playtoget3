<?php

namespace App\Models;

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

    public function country()
    {
        return $this->belongsTo(GeoCountry::class);
    }

    public function region()
    {
        return $this->belongsTo(GeoRegion::class);
    }

    public function targets()
    {
        return $this->hasMany(GeoTarget::class, 'city_id');
    }
}
