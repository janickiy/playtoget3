<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friend extends BaseModel
{
    protected $table = 'friends';

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'added',
    ];

    protected function casts(): array
    {
        return [
            'added' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}
