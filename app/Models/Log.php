<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends BaseModel
{
    use StaticTableName;

    protected $table = 'logs';

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

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
