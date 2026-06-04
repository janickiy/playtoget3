<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;


class Catalog extends Model
{
    use StaticTableName;

    protected $table = 'catalog';

    protected $fillable = [
        'name',
    ];
}
