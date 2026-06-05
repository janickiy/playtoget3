<?php

namespace App\Models;

class SportEvent extends BaseModel
{
    protected $table = 'sport_events';

    public $timestamps = true;

    protected $fillable = [
        'header',
        'announce',
        'content',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
