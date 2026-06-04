<?php

namespace App\Models;

class GeoTarget extends BaseModel
{
    protected $table = 'geo_target';

    protected $fillable = [
        'target_type',
        'target_id',
        'country_id',
        'region_id',
        'city_id',
    ];

    public function target()
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }

    public function country()
    {
        return $this->belongsTo(GeoCountry::class);
    }

    public function region()
    {
        return $this->belongsTo(GeoRegion::class);
    }

    public function city()
    {
        return $this->belongsTo(GeoCity::class);
    }
}
