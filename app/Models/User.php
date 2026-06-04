<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, StaticTableName;

    protected $fillable = [
        'email',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'firstname',
        'lastname',
        'secondname',
        'sex',
        'birthday',
        'phone',
        'contact_email',
        'skype',
        'website',
        'about',
        'about_sport',
        'avatar',
        'cover_page',
        'country',
        'region',
        'city',
        'language',
        'confirmed',
        'banned',
        'deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'confirmed' => 'boolean',
            'banned' => 'boolean',
            'deleted' => 'boolean',
        ];
    }

    public function displayName(): string
    {
        $name = trim(sprintf('%s %s', (string) $this->firstname, (string) $this->lastname));

        return $name !== '' ? $name : $this->email;
    }
}
