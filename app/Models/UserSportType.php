<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSportType extends BaseModel
{
    protected $table = 'users_sport_types';

    protected $fillable = [
        'user_id',
        'sport_type',
        'sport_level_id',
        'search_team',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function sportType(): BelongsTo
    {
        return $this->belongsTo(SportType::class, 'sport_type');
    }

    /**
     * @return BelongsTo
     */
    public function sportLevel(): BelongsTo
    {
        return $this->belongsTo(SportLevel::class);
    }
}
