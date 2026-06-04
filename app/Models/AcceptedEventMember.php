<?php

namespace App\Models;

class AcceptedEventMember extends BaseModel
{
    protected $table = 'accepted_event_members';

    protected $fillable = [
        'eventable_type',
        'member_id',
        'role',
        'event_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function member()
    {
        return $this->morphTo(__FUNCTION__, 'eventable_type', 'member_id');
    }
}
