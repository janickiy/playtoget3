<?php

namespace App\Models;

class Occupation extends BaseModel
{
    protected $table = 'occupations';

    protected $fillable = [
        'kind',
        'user_id',
        'name',
        'description',
        'month_start',
        'year_start',
        'month_finish',
        'year_finish',
        'city',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function geoTargets()
    {
        return $this->morphMany(GeoTarget::class, 'target', 'target_type', 'target_id');
    }
}
