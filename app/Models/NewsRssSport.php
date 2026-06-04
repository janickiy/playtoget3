<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class NewsRssSport extends Model
{
    use StaticTableName;

    protected $table = 'news_rss_sport';

    protected $fillable = [
        'title',
        'link',
        'description',
        'created_at',
        'updated_at',
    ];
}
