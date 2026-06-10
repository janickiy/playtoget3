<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    /**
     * @return BelongsTo
     */
    public function sportType(): BelongsTo
    {
        return $this->belongsTo(SportType::class, 'sport_type');
    }

    /**
     * @return HasMany
     */
    public function acceptedMembers(): HasMany
    {
        return $this->hasMany(AcceptedEventMember::class);
    }

    /**
     * @return BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'accepted_event_members', 'event_id', 'member_id')->wherePivot('eventable_type', 'user')->withPivot(['role', 'eventable_type']);
    }

    /**
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'content_id');
    }

    /**
     * @return MorphMany
     */
    public function geoTargets(): MorphMany
    {
        return $this->morphMany(GeoTarget::class, 'target', 'target_type', 'target_id');
    }
}
