<?php

namespace App\Models;

use App\Http\Traits\File;
use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Settings extends Model
{
    use StaticTableName, File;

    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key_cd',
        'name',
        'type',
        'display_value',
        'value',
        'published',
    ];


    /**
     * @param $value
     * @return void
     */
    public function setKeyCdAttribute($value): void
    {
        $this->attributes['key_cd'] = str_replace(' ', '_', strtoupper($value));
    }

    /**
     * @return string
     */
    public function getTypeAttribute()
    {
        return $this->attributes['type'] = strtoupper($this->attributes['type']);
    }

    /**
     * @return array|string
     */
    public function getValueAttribute()
    {
        if (($this->attributes['type'] ?? null) == 'FILE') {
            return Storage::disk('public')->url($this->table . '/' . $this->attributes['value']);
        }

        return $this->attributes['value'];
    }

    /**
     * @return mixed
     */
    public function filePath()
    {
        return $this->attributes['value'];
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('published', 1);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function scopeRemove(): void
    {
        self::deleteFile($this->filePath(), $this->table);

        $this->delete();
    }
}
