<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'consent_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'consent_type', 'consent_for',
        'reference_type', 'reference_id', 'consent_date', 'consent_time',
        'is_given', 'given_by', 'relationship', 'witness_name', 'doctor_id',
        'consent_form_path', 'digital_signature_path', 'notes',
        'revoked_at', 'revoked_by', 'revocation_reason', 'created_by',
    ];

    protected $casts = [
        'consent_date' => 'date',
        'is_given' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function isActive(): bool
    {
        return $this->is_given && !$this->revoked_at;
    }

    public function scopeActive($query)
    {
        return $query->where('is_given', true)->whereNull('revoked_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }
}
