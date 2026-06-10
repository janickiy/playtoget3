<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AcceptedEventMember extends BaseModel
{
    protected $table = 'accepted_event_members';

    protected $fillable = [
        'eventable_type',
        'member_id',
        'role',
        'event_id',
    ];

    /**
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return MorphTo
     */
    public function member(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'eventable_type', 'member_id');
    }
}
