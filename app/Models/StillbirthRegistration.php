<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StillbirthRegistration extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'stillbirth_id';

    protected $fillable = [
        'hospital_id', 'registration_number', 'ipd_id', 'mother_patient_id',
        'father_name', 'father_aadhar', 'mother_name', 'mother_aadhar', 'mother_age_at_delivery',
        'permanent_address', 'permanent_city', 'permanent_district', 'permanent_state', 'permanent_pincode',
        'date_of_delivery', 'time_of_delivery', 'place_of_delivery',
        'gender', 'weight_kg', 'gestational_age_weeks', 'delivery_type',
        'cause_of_fetal_death', 'was_alive_at_labor_start', 'attending_doctor_id', 'complications',
        'informant_name', 'informant_relation',
        'certificate_number', 'certificate_issue_date',
        'is_govt_registered', 'govt_registration_number',
        'status', 'created_by',
    ];

    protected $casts = [
        'date_of_delivery' => 'date',
        'weight_kg' => 'decimal:2',
        'was_alive_at_labor_start' => 'boolean',
        'is_govt_registered' => 'boolean',
        'certificate_issue_date' => 'date',
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
        $prefix = 'SB';
        $year = now()->format('Y');
        $count = static::where('hospital_id', $hospitalId)
            ->whereYear('created_at', $year)
            ->count();
        return $prefix . $year . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}
