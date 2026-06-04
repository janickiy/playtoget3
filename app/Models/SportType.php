<?php

namespace App\Models;

class SportType extends BaseModel
{
    protected $table = 'sport_types';

    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function userSportTypes()
    {
        return $this->hasMany(UserSportType::class, 'sport_type');
    }

    public function communities()
    {
        return $this->hasMany(Community::class, 'sport_type');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'sport_type');
    }
}
