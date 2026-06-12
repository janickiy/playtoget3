<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;

class FailedJob extends BaseModel
{
    use StaticTableName;

    protected $table = 'failed_jobs';

    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];
}
