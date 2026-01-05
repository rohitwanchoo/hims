<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FhirSubscription extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'subscription_id';

    protected $fillable = [
        'hospital_id', 'endpoint_id', 'subscription_fhir_id',
        'status', 'channel_type', 'channel_endpoint',
        'channel_payload', 'channel_headers', 'criteria',
        'reason', 'end_date', 'error_message',
    ];

    protected $hidden = [
        'channel_headers',
    ];

    protected $casts = [
        'channel_headers' => 'array',
        'end_date' => 'datetime',
    ];

    protected function channelHeaders(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(decrypt($value), true) : [],
            set: fn ($value) => $value ? encrypt(json_encode($value)) : null,
        );
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(FhirEndpoint::class, 'endpoint_id', 'endpoint_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && (!$this->end_date || $this->end_date->isFuture());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    public function matchesCriteria(string $resourceType, array $resource): bool
    {
        if (!$this->criteria) {
            return true;
        }

        // Parse criteria string (e.g., "Observation?code=vital-signs")
        $parts = explode('?', $this->criteria);
        if ($parts[0] !== $resourceType) {
            return false;
        }

        // For simple implementation, just check resource type
        return true;
    }
}
