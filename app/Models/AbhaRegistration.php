<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AbhaRegistration extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'abha_registration_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'abha_number', 'abha_address', 'health_id',
        'name', 'date_of_birth', 'gender', 'mobile', 'email',
        'kyc_status', 'kyc_type', 'kyc_document_number',
        'linked_at', 'consent_given', 'consent_datetime',
        'hip_id', 'phr_address', 'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'linked_at' => 'datetime',
        'consent_given' => 'boolean',
        'consent_datetime' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function authToken(): HasOne
    {
        return $this->hasOne(AbhaAuthToken::class, 'abha_registration_id', 'abha_registration_id')
            ->latest();
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(AbdmHealthRecord::class, 'abha_registration_id', 'abha_registration_id');
    }

    public function consentRequests(): HasMany
    {
        return $this->hasMany(AbdmConsentRequest::class, 'abha_registration_id', 'abha_registration_id');
    }

    public function isVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }

    public function hasValidAbha(): bool
    {
        return $this->abha_number && $this->kyc_status === 'verified';
    }

    public function scopeVerified($query)
    {
        return $query->where('kyc_status', 'verified');
    }
}
