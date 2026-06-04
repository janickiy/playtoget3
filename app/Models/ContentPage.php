<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    use StaticTableName;

    protected $table = 'content';

    protected $fillable = [
        'title',
        'text',
        'hide',
    ];
}
