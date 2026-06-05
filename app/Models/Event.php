<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use StaticTableName;

    protected $fillable = [
        'name',
        'date_from',
        'date_to',
        'description',
        'sport_type',
        'cover_page',
        'place',
        'address',
        'moderate',
        'banned',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'datetime',
            'date_to' => 'datetime',
            'moderate' => 'boolean',
            'banned' => 'boolean',
        ];
    }

    public function sportType()
    {
        return $this->belongsTo(SportType::class, 'sport_type');
    }

    public function acceptedMembers()
    {
        return $this->hasMany(AcceptedEventMember::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'accepted_event_members', 'event_id', 'member_id')->wherePivot('eventable_type', 'user')->withPivot(['role', 'eventable_type']);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'content_id');
    }

    public function geoTargets()
    {
        return $this->morphMany(GeoTarget::class, 'target', 'target_type', 'target_id');
    }
}
