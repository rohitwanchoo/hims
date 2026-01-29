<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DischargeSummary extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'discharge_summary_id';

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'ipd_id',
        'discharge_summary_number',
        'admission_date',
        'discharge_date',
        'admission_type',
        'chief_complaints',
        'history_of_present_illness',
        'past_medical_history',
        'family_history',
        'physical_examination',
        'vital_signs',
        'provisional_diagnosis',
        'final_diagnosis',
        'secondary_diagnosis',
        'icd_codes',
        'course_in_hospital',
        'procedures_performed',
        'operation_notes',
        'investigations',
        'treatment_given',
        'medications_on_admission',
        'medications_on_discharge',
        'condition_at_discharge',
        'discharge_advice',
        'follow_up_instructions',
        'follow_up_date',
        'dietary_instructions',
        'activity_restrictions',
        'treating_doctor_id',
        'consultant_doctor_id',
        'created_by',
        'abha_address',
        'shared_with_abdm',
        'abdm_shared_at',
        'abdm_document_id',
        'notes',
        'status',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'follow_up_date' => 'date',
        'shared_with_abdm' => 'boolean',
        'abdm_shared_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function treatingDoctor()
    {
        return $this->belongsTo(Doctor::class, 'treating_doctor_id', 'doctor_id');
    }

    public function consultantDoctor()
    {
        return $this->belongsTo(Doctor::class, 'consultant_doctor_id', 'doctor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function customFieldValues()
    {
        return $this->hasMany(DischargeSummaryCustomFieldValue::class, 'discharge_summary_id', 'discharge_summary_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->discharge_summary_number) {
                $model->discharge_summary_number = self::generateSummaryNumber();
            }
        });
    }

    private static function generateSummaryNumber()
    {
        $date = date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'DS-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
