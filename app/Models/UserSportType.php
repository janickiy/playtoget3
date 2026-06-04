<?php

namespace App\Models;

class UserSportType extends BaseModel
{
    protected $table = 'users_sport_types';

    protected $fillable = [
        'user_id',
        'sport_type',
        'sport_level_id',
        'search_team',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sportType()
    {
        return $this->belongsTo(SportType::class, 'sport_type');
    }

    public function sportLevel()
    {
        return $this->belongsTo(SportLevel::class);
    }
}
