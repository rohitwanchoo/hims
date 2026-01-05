<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'setting_id';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
        'description',
    ];

    public static function get($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }

    public static function set($key, $value, $group = 'general', $description = null)
    {
        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_group' => $group,
                'description' => $description,
            ]
        );
    }
}
