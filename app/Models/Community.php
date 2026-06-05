<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use StaticTableName;

    protected $table = 'communities';

    protected $fillable = [
        'type',
        'banned',
        'name',
        'about',
        'avatar',
        'cover_page',
        'place',
        'sport_type',
        'moderate',
    ];

    protected function casts(): array
    {
        return [
            'banned' => 'boolean',
            'moderate' => 'boolean',
        ];
    }

    public function settings()
    {
        return $this->hasOne(CommunitySetting::class);
    }

    public function roles()
    {
        return $this->hasMany(CommunityRole::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'community_roles')->withPivot(['role', 'role_description']);
    }

    public function sportType()
    {
        return $this->belongsTo(SportType::class, 'sport_type');
    }

    public function photoalbums()
    {
        return $this->hasMany(Photoalbum::class, 'owner_id')->where('photoalbumable_type', $this->type);
    }

    public function videoalbums()
    {
        return $this->hasMany(Videoalbum::class, 'owner_id')->where('videoalbumable_type', $this->type);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'content_id')->where('commentable_type', $this->type);
    }

    public function geoTargets()
    {
        return $this->hasMany(GeoTarget::class, 'target_id')->where('target_type', $this->type);
    }
}
