<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SmsGateway extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'gateway_id';

    protected $fillable = [
        'hospital_id',
        'gateway_name',
        'provider',
        'api_url',
        'api_key',
        'api_secret',
        'sender_id',
        'settings',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    protected function apiSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault(int $hospitalId): ?self
    {
        return static::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }
}
