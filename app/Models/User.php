<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Http\Traits\StaticTableName;
use App\Service\UserOnlineStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, StaticTableName;

    protected $fillable = [
        'email',
        'password',
        'remember_token',
        'firstname',
        'lastname',
        'secondname',
        'sex',
        'birthday',
        'phone',
        'contact_email',
        'telegram',
        'whatsapp',
        'viber',
        'website',
        'about',
        'about_sport',
        'avatar',
        'cover_page',
        'country',
        'region',
        'city',
        'status',
        'confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'status' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function statusEnum(): UserStatus
    {
        return UserStatus::tryFrom((int) $this->status) ?? UserStatus::New;
    }

    public function isConfirmed(): bool
    {
        return $this->statusEnum() === UserStatus::Confirmed;
    }

    public function isBlocked(): bool
    {
        return $this->statusEnum() === UserStatus::Blocked;
    }

    public function isDeleted(): bool
    {
        return $this->statusEnum() === UserStatus::Deleted;
    }

    public function isActive(): bool
    {
        return $this->statusEnum()->isActive();
    }

    public function isOnline(): bool
    {
        return app(UserOnlineStatusService::class)->isOnline($this);
    }

    /**
     * @return HasOne
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * @return HasOne
     */
    public function activity(): HasOne
    {
        return $this->hasOne(UserActivity::class);
    }

    /**
     * @return HasMany
     */
    public function sportTypes(): HasMany
    {
        return $this->hasMany(UserSportType::class);
    }

    /**
     * @return HasMany
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'owner_id');
    }

    /**
     * @return MorphMany
     */
    public function photoalbums(): MorphMany
    {
        return $this->morphMany(PhotoAlbums::class, 'photoalbumable', 'photoalbumable_type', 'owner_id');
    }

    /**
     * @return HasMany
     */
    public function attachedPhotoalbums(): HasMany
    {
        return $this->hasMany(PhotoAlbums::class, 'owner_id')->where('photoalbumable_type', 'user_attach');
    }

    /**
     * @return HasMany
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'owner_id');
    }

    /**
     * @return MorphMany
     */
    public function videoalbums(): MorphMany
    {
        return $this->morphMany(VideoAlbums::class, 'videoalbumable', 'videoalbumable_type', 'owner_id');
    }

    /**
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'content_id');
    }

    /**
     * @return HasMany
     */
    public function authoredComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * @return HasMany
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * @return HasMany
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    public function friends(): HasMany
    {
        return $this->hasMany(Friend::class);
    }

    /**
     * @return HasMany
     */
    public function friendOf(): HasMany
    {
        return $this->hasMany(Friend::class, 'friend_id');
    }

    /**
     * @return HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * @return HasMany
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    /**
     * @return BelongsToMany
     */
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_roles')->withPivot(['role', 'role_description']);
    }

    /**
     * @return MorphMany
     */
    public function acceptedEventMemberships(): MorphMany
    {
        return $this->morphMany(AcceptedEventMember::class, 'member', 'eventable_type', 'member_id');
    }

    /**
     * @return MorphMany
     */
    public function geoTargets(): MorphMany
    {
        return $this->morphMany(GeoTarget::class, 'target', 'target_type', 'target_id');
    }

    /**
     * @return HasMany
     */
    public function occupations(): HasMany
    {
        return $this->hasMany(Occupation::class);
    }

    /**
     * @return HasMany
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * @return HasMany
     */
    public function videoViews(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    /**
     * @return string
     */
    public function displayName(): string
    {
        $name = trim(sprintf('%s %s', (string) $this->firstname, (string) $this->lastname));

        return $name !== '' ? $name : $this->email;
    }
}
