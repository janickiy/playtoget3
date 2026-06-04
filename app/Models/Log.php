<?php

namespace App\Models;

class Log extends BaseModel
{
    protected $table = 'log';

    protected $fillable = [
        'user_id',
        'ip',
        'user_agent',
        'last_sign_in_at',
    ];

    protected function casts(): array
    {
        return [
            'last_sign_in_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
