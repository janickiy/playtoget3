<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, StaticTableName;

    protected $fillable = [
        'email',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'firstname',
        'lastname',
        'secondname',
        'sex',
        'birthday',
        'phone',
        'contact_email',
        'skype',
        'website',
        'about',
        'about_sport',
        'avatar',
        'cover_page',
        'country',
        'region',
        'city',
        'language',
        'confirmed',
        'banned',
        'deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'confirmed' => 'boolean',
            'banned' => 'boolean',
            'deleted' => 'boolean',
        ];
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function activity()
    {
        return $this->hasOne(UserActivity::class);
    }

    public function roles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function sportTypes()
    {
        return $this->hasMany(UserSportType::class);
    }

    public function photos()
    {
        return $this->hasMany(Photo::class, 'owner_id');
    }

    public function photoalbums()
    {
        return $this->morphMany(Photoalbum::class, 'photoalbumable', 'photoalbumable_type', 'owner_id');
    }

    public function attachedPhotoalbums()
    {
        return $this->hasMany(Photoalbum::class, 'owner_id')->where('photoalbumable_type', 'user_attach');
    }

    public function videos()
    {
        return $this->hasMany(Video::class, 'owner_id');
    }

    public function videoalbums()
    {
        return $this->morphMany(Videoalbum::class, 'videoalbumable', 'videoalbumable_type', 'owner_id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'content_id');
    }

    public function authoredComments()
    {
        return $this->hasMany(Comment::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function friends()
    {
        return $this->hasMany(Friend::class);
    }

    public function friendOf()
    {
        return $this->hasMany(Friend::class, 'friend_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function communities()
    {
        return $this->belongsToMany(Community::class, 'community_roles')->withPivot(['role', 'role_description']);
    }

    public function acceptedEventMemberships()
    {
        return $this->morphMany(AcceptedEventMember::class, 'member', 'eventable_type', 'member_id');
    }

    public function geoTargets()
    {
        return $this->morphMany(GeoTarget::class, 'target', 'target_type', 'target_id');
    }

    public function occupations()
    {
        return $this->hasMany(Occupation::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function videoViews()
    {
        return $this->hasMany(VideoView::class);
    }

    public function displayName(): string
    {
        $name = trim(sprintf('%s %s', (string) $this->firstname, (string) $this->lastname));

        return $name !== '' ? $name : $this->email;
    }
}
