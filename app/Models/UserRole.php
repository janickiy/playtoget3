<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends BaseModel
{
    use StaticTableName;

    protected $table = 'users_roles';

    protected $fillable = [
        'user_id',
        'role_id',
        'descr',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
