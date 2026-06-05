<?php

namespace App\Models;

class Message extends BaseModel
{
    protected $table = 'messages';

    public $timestamps = true;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable', 'type', 'content_id');
    }
}
