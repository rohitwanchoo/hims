<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbdmConsentRequest extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'consent_request_id';

    protected $fillable = [
        'hospital_id', 'abha_registration_id', 'patient_id',
        'consent_request_id_abdm', 'purpose', 'purpose_code',
        'hi_types', 'date_range_from', 'date_range_to',
        'requester_name', 'requester_id', 'hiu_id',
        'status', 'consent_artefact_id', 'granted_at', 'denied_at',
        'revoked_at', 'expiry_at', 'signature',
    ];

    protected $casts = [
        'hi_types' => 'array',
        'date_range_from' => 'date',
        'date_range_to' => 'date',
        'granted_at' => 'datetime',
        'denied_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expiry_at' => 'datetime',
    ];

    public function abhaRegistration(): BelongsTo
    {
        return $this->belongsTo(AbhaRegistration::class, 'abha_registration_id', 'abha_registration_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(AbdmHealthRecord::class, 'consent_id', 'consent_request_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'granted'
            && $this->expiry_at
            && $this->expiry_at->isFuture()
            && !$this->revoked_at;
    }

    public function isExpired(): bool
    {
        return $this->expiry_at && $this->expiry_at->isPast();
    }

    public function scopeGranted($query)
    {
        return $query->where('status', 'granted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'requested');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'granted')
            ->where('expiry_at', '>', now())
            ->whereNull('revoked_at');
    }
}
