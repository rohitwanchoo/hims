<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingDiagnosis extends Model
{
    use BelongsToHospital;

    protected $table = 'coding_diagnoses';
    protected $primaryKey = 'coding_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'reference_type', 'reference_id',
        'diagnosis_type', 'diagnosis_text', 'icd_code', 'icd_description',
        'is_principal', 'coded_by', 'coded_at', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'is_principal' => 'boolean',
        'coded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function codedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coded_by', 'user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('verified_at');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }
}
