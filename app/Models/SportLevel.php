<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SportLevel extends BaseModel
{
    protected $table = 'sport_level';

    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany
     */
    public function userSportTypes(): HasMany
    {
        return $this->hasMany(UserSportType::class, 'sport_level_id');
    }
}
