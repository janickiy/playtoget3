<?php

namespace App\Models;

class GeoCountry extends BaseModel
{
    protected $table = 'geo_country';

    protected $fillable = [
        'name_ru',
        'name_en',
        'code',
        'sort',
    ];

    public function regions()
    {
        return $this->hasMany(GeoRegion::class, 'country_id');
    }

    public function cities()
    {
        return $this->hasMany(GeoCity::class, 'country_id');
    }

    public function targets()
    {
        return $this->hasMany(GeoTarget::class, 'country_id');
    }
}
