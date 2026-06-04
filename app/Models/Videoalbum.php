<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Videoalbum extends Model
{
    use StaticTableName;

    protected $table = 'videoalbums';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
        'videoalbumable_type',
        'owner_id',
    ];

    public function videoalbumable()
    {
        return $this->morphTo(__FUNCTION__, 'videoalbumable_type', 'owner_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
