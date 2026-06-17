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
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_MODERATOR => 'Moderator',
        self::ROLE_EDITOR => 'Editor',
    ];

    protected $fillable = [
        'login',
        'password',
        'name',
        'role',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
