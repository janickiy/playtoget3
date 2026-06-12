<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportType extends BaseModel
{
    use StaticTableName;

    protected $table = 'sport_types';

    protected $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function userSportTypes(): HasMany
    {
        return $this->hasMany(UserSportType::class, 'sport_type');
    }

    /**
     * @return HasMany
     */
    public function communities(): HasMany
    {
        return $this->hasMany(Community::class, 'sport_type');
    }

    /**
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'sport_type');
    }
}
