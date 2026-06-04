<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use StaticTableName;

    protected $table = 'feedback';

    protected $fillable = [
        'subject',
        'name',
        'email',
        'message',
    ];
}
