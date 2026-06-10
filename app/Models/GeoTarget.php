<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    /**
     * @return MorphTo
     */
    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }

    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(GeoCountry::class);
    }

    /**
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(GeoRegion::class);
    }

    /**
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(GeoCity::class);
    }
}
