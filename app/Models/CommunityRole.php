<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityRole extends BaseModel
{
    use StaticTableName;

    protected $table = 'community_roles';

    protected $fillable = [
        'user_id',
        'community_id',
        'role',
        'role_description',
    ];

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
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
