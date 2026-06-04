<?php

namespace App\Models;

class Migration extends BaseModel
{
    protected $table = 'migrations';

    protected $fillable = [
        'migration',
        'batch',
    ];
}
