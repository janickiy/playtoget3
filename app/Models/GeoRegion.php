<?php

namespace App\Models;

class GeoRegion extends BaseModel
{
    protected $table = 'geo_region';

    protected $fillable = [
        'country_id',
        'name_ru',
        'name_en',
        'sort',
    ];

    public function country()
    {
        return $this->belongsTo(GeoCountry::class);
    }

    public function cities()
    {
        return $this->hasMany(GeoCity::class, 'region_id');
    }

    public function targets()
    {
        return $this->hasMany(GeoTarget::class, 'region_id');
    }
}
