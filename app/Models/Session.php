<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends BaseModel
{
    protected $table = 'sessions';

    protected $primaryKey = 'session_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'session_id',
        'token',
        'user_id',
        'expiration_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
