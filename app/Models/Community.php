<?php

namespace App\Models;

use App\Enums\CommunityStatus;
use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Community extends Model
{
    use StaticTableName;

    protected $table = 'communities';

    protected $fillable = [
        'type',
        'name',
        'about',
        'avatar',
        'cover_page',
        'place',
        'sport_type',
        'status',
        'recommended',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'recommended' => 'boolean',
        ];
    }

    public function statusEnum(): CommunityStatus
    {
        return CommunityStatus::tryFrom((int) $this->status) ?? CommunityStatus::New;
    }

    public function isVisible(): bool
    {
        return $this->statusEnum()->isVisible();
    }

    /**
     * @return HasOne
     */
    public function settings(): HasOne
    {
        return $this->hasOne(CommunitySetting::class);
    }

    /**
     * @return HasMany
     */
    public function roles(): HasMany
    {
        return $this->hasMany(CommunityRole::class);
    }

    /**
     * @return BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_roles')->withPivot(['role', 'role_description']);
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
    public function photoalbums(): HasMany
    {
        return $this->hasMany(PhotoAlbums::class, 'owner_id')->where('photoalbumable_type', $this->type);
    }

    /**
     * @return HasMany
     */
    public function videoalbums(): HasMany
    {
        return $this->hasMany(VideoAlbums::class, 'owner_id')->where('videoalbumable_type', $this->type);
    }

    /**
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_id')->where('commentable_type', $this->type);
    }

    /**
     * @return HasMany
     */
    public function geoTargets(): HasMany
    {
        return $this->hasMany(GeoTarget::class, 'target_id')->where('target_type', $this->type);
    }
}
