<?php

namespace App\Models;

class SportLevel extends BaseModel
{
    protected $table = 'sport_level';

    protected $fillable = [
        'name',
    ];

    public function userSportTypes()
    {
        return $this->hasMany(UserSportType::class, 'sport_level_id');
    }
}
