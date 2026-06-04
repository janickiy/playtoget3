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
}
