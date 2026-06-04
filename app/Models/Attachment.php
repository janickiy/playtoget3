<?php

namespace App\Models;

class Attachment extends BaseModel
{
    protected $table = 'attachment';

    protected $fillable = [
        'type',
        'content_id',
        'photo_id',
    ];

    public function attachable()
    {
        return $this->morphTo(__FUNCTION__, 'type', 'content_id');
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }
}
