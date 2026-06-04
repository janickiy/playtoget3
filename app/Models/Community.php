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
}
