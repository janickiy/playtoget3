<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Photoalbum extends Model
{
    use StaticTableName;

    protected $table = 'photoalbums';

    protected $fillable = [
        'name',
        'photoalbumable_type',
        'owner_id',
    ];

    public function photoalbumable()
    {
        return $this->morphTo(__FUNCTION__, 'photoalbumable_type', 'owner_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }
}
