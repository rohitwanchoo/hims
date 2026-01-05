<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbdmHealthRecord extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'record_id';

    protected $fillable = [
        'hospital_id', 'abha_registration_id', 'patient_id',
        'care_context_reference', 'care_context_type', 'care_context_id',
        'hi_type', 'fhir_bundle', 'status', 'consent_id',
        'shared_at', 'acknowledged_at', 'error_message',
    ];

    protected $casts = [
        'fhir_bundle' => 'array',
        'shared_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function abhaRegistration(): BelongsTo
    {
        return $this->belongsTo(AbhaRegistration::class, 'abha_registration_id', 'abha_registration_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function consentRequest(): BelongsTo
    {
        return $this->belongsTo(AbdmConsentRequest::class, 'consent_id', 'consent_request_id');
    }

    public function getCareContext()
    {
        return match ($this->care_context_type) {
            'opd' => OpdVisit::find($this->care_context_id),
            'ipd' => IpdAdmission::find($this->care_context_id),
            'lab' => LabOrder::find($this->care_context_id),
            'radiology' => RadiologyOrder::find($this->care_context_id),
            default => null,
        };
    }

    public function scopeByHiType($query, string $hiType)
    {
        return $query->where('hi_type', $hiType);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeShared($query)
    {
        return $query->where('status', 'shared');
    }
}
