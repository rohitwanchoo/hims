<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FhirEndpoint extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'endpoint_id';

    protected $fillable = [
        'hospital_id', 'endpoint_name', 'endpoint_type',
        'base_url', 'auth_type', 'auth_credentials',
        'fhir_version', 'supported_resources', 'is_active',
        'last_connected_at', 'connection_status',
    ];

    protected $hidden = [
        'auth_credentials',
    ];

    protected $casts = [
        'supported_resources' => 'array',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    protected function authCredentials(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    public function supportsResource(string $resourceType): bool
    {
        return in_array($resourceType, $this->supported_resources ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSenders($query)
    {
        return $query->where('endpoint_type', 'sender');
    }

    public function scopeReceivers($query)
    {
        return $query->where('endpoint_type', 'receiver');
    }
}
