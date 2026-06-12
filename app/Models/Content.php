<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;

class Content extends BaseModel
{
    use StaticTableName;

    protected $table = 'content';

    protected $fillable = [
        'title',
        'text',
        'hide',
    ];
}
