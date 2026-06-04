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
}
