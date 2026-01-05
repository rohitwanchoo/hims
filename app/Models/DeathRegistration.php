<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeathRegistration extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'death_id';

    protected $fillable = [
        'hospital_id', 'registration_number', 'patient_id', 'ipd_id',
        'deceased_name', 'deceased_aadhar', 'date_of_birth',
        'age_years', 'age_months', 'age_days', 'gender', 'marital_status',
        'occupation', 'nationality', 'religion',
        'permanent_address', 'permanent_city', 'permanent_district', 'permanent_state', 'permanent_pincode',
        'date_of_death', 'time_of_death', 'place_of_death', 'place_details',
        'is_medically_certified', 'cause_of_death_immediate', 'cause_of_death_antecedent',
        'cause_of_death_underlying', 'other_conditions', 'manner_of_death',
        'was_pregnant', 'pregnancy_contribution',
        'is_autopsy_performed', 'autopsy_findings',
        'is_mlc_case', 'mlc_number', 'police_station',
        'certifying_doctor_id', 'father_name', 'mother_name', 'spouse_name',
        'informant_name', 'informant_relation', 'informant_address',
        'certificate_number', 'certificate_issue_date',
        'is_govt_registered', 'govt_registration_number', 'govt_registration_date',
        'cremation_burial_place', 'body_handed_to', 'body_handed_relation', 'body_handed_at',
        'status', 'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_death' => 'date',
        'is_medically_certified' => 'boolean',
        'pregnancy_contribution' => 'boolean',
        'is_autopsy_performed' => 'boolean',
        'is_mlc_case' => 'boolean',
        'is_govt_registered' => 'boolean',
        'certificate_issue_date' => 'date',
        'govt_registration_date' => 'date',
        'body_handed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function ipdAdmission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function certifyingDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'certifying_doctor_id', 'doctor_id');
    }

    public static function generateRegistrationNumber(int $hospitalId): string
    {
        $prefix = 'DR';
        $year = now()->format('Y');
        $count = static::where('hospital_id', $hospitalId)
            ->whereYear('created_at', $year)
            ->count();
        return $prefix . $year . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}
