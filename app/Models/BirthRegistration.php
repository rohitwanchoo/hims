<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthRegistration extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'birth_id';

    protected $fillable = [
        'hospital_id', 'registration_number', 'ipd_id', 'mother_patient_id',
        'father_name', 'father_aadhar', 'father_occupation', 'father_education', 'father_nationality',
        'mother_name', 'mother_aadhar', 'mother_occupation', 'mother_education', 'mother_age_at_delivery', 'mother_nationality',
        'permanent_address', 'permanent_city', 'permanent_district', 'permanent_state', 'permanent_pincode',
        'date_of_birth', 'time_of_birth', 'place_of_birth', 'birth_type', 'gender', 'weight_kg',
        'birth_order', 'delivery_type', 'pregnancy_duration_weeks', 'attending_doctor_id',
        'complications', 'apgar_score_1min', 'apgar_score_5min',
        'is_multiple_birth', 'multiple_birth_order', 'child_name',
        'informant_name', 'informant_relation', 'informant_address',
        'certificate_number', 'certificate_issue_date',
        'is_govt_registered', 'govt_registration_number', 'govt_registration_date',
        'status', 'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'weight_kg' => 'decimal:2',
        'is_multiple_birth' => 'boolean',
        'is_govt_registered' => 'boolean',
        'certificate_issue_date' => 'date',
        'govt_registration_date' => 'date',
    ];

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'mother_patient_id', 'patient_id');
    }

    public function ipdAdmission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function attendingDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'attending_doctor_id', 'doctor_id');
    }

    public static function generateRegistrationNumber(int $hospitalId): string
    {
        $prefix = 'BR';
        $year = now()->format('Y');
        $count = static::where('hospital_id', $hospitalId)
            ->whereYear('created_at', $year)
            ->count();
        return $prefix . $year . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}
