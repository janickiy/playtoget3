<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends BaseModel
{
    protected $table = 'user_activity';

    protected $fillable = [
        'user_id',
        'last_activity',
    ];

    protected function casts(): array
    {
        return [
            'last_activity' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
