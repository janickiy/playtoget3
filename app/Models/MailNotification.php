<?php

namespace App\Models;

class MailNotification extends BaseModel
{
    protected $table = 'mail_notification';

    protected $fillable = [
        'subject_ru',
        'subject_en',
        'content_ru',
        'content_en',
    ];
}
