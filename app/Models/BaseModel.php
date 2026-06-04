<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use StaticTableName;

    public $timestamps = false;

    protected $guarded = [];
}
