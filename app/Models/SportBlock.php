<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class SportBlock extends Model
{
    use StaticTableName;

    protected $fillable = [
        'name',
        'about',
        'place',
        'address',
        'phone',
        'email',
        'avatar',
        'website',
        'type',
        'owner_id',
        'active',
        'banned',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'banned' => 'boolean',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function photoalbums()
    {
        return $this->hasMany(Photoalbum::class, 'owner_id')->where('photoalbumable_type', $this->type);
    }

    public function geoTargets()
    {
        return $this->hasMany(GeoTarget::class, 'target_id')->where('target_type', $this->type);
    }
}
