<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, StaticTableName;

    protected $table = 'admin';

    public const string ROLE_ADMIN = 'admin';
    public const string ROLE_MODERATOR = 'moderator';
    public const string ROLE_EDITOR = 'editor';

    public static array $role_name = [
        self::ROLE_ADMIN => 'Админ',
        self::ROLE_MODERATOR => 'Модератор',
        self::ROLE_EDITOR => 'Редактор'
    ];

    protected $fillable = [
        'name', 'role', 'login', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
