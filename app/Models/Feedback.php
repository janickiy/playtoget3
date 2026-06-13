<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use StaticTableName;

    protected $table = 'feedback';

    public $timestamps = false;

    protected $fillable = [
        'subject',
        'name',
        'email',
        'message',
        'status',
        'answer',
        'time',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'time' => 'datetime',
        ];
    }

    public function statusEnum(): FeedbackStatus
    {
        return FeedbackStatus::tryFrom((int) $this->status) ?? FeedbackStatus::New;
    }

    public function statusLabel(): string
    {
        return $this->statusEnum()->label();
    }
}
