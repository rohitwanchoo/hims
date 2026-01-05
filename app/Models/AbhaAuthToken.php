<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AbhaAuthToken extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'token_id';

    protected $fillable = [
        'hospital_id', 'abha_registration_id', 'token_type',
        'access_token', 'refresh_token', 'expires_at', 'scope',
    ];

    protected $hidden = [
        'access_token', 'refresh_token',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    public function abhaRegistration(): BelongsTo
    {
        return $this->belongsTo(AbhaRegistration::class, 'abha_registration_id', 'abha_registration_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && $this->access_token;
    }
}
