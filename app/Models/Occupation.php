<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany
     */
    public function geoTargets(): MorphMany
    {
        return $this->morphMany(GeoTarget::class, 'target', 'target_type', 'target_id');
    }
}
