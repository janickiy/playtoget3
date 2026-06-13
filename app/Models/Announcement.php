<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use StaticTableName;

    protected $fillable = [
        'title',
        'text',
        'slug',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
        ];
    }

    public function isPublished(): bool
    {
        return (bool) $this->published;
    }
}
