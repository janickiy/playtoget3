<?php

namespace App\Models;

class Content extends BaseModel
{
    protected $table = 'content';

    protected $fillable = [
        'title',
        'text',
        'hide',
    ];
}
