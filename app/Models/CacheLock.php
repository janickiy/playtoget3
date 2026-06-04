<?php

namespace App\Models;

class CacheLock extends BaseModel
{
    protected $table = 'cache_locks';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'owner',
        'expiration',
    ];
}
