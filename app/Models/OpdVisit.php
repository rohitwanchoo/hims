<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class OpdVisit extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'opd_id';

    protected $fillable = [
        'hospital_id',
        'opd_number',
        'registration_purpose',
        'patient_id',
        'visit_date',
        'registration_expiry_date',
        'visit_time',
        'token_number',
        'department_id',
        'doctor_id',
        'group_id',
        'reference_doctor_id',
        'class_id',
        'health_package_id',
        'visit_type',
        'charge_type',
        'referral_doctor',
        'chief_complaints',
        'history_of_illness',
        'examination_notes',
        'diagnosis',
        'advice',
        'followup_date',
        'followup_instructions',
        'is_free_followup',
        'previous_visit_id',
        'vitals_bp_systolic',
        'vitals_bp_diastolic',
        'vitals_pulse',
        'vitals_temperature',
        'vitals_spo2',
        'vitals_weight',
        'vitals_height',
        'consultation_fee',
        'is_mlc',
        'mlc_number',
        'police_station',
        'is_insurance',
        'insurance_company_name',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'bill_id',
        'entry_card_id',
        'status',
        'cancel_reason_id',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'registration_expiry_date' => 'date',
        'followup_date' => 'date',
        'vitals_temperature' => 'decimal:1',
        'vitals_weight' => 'decimal:2',
        'vitals_height' => 'decimal:2',
        'consultation_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'is_mlc' => 'boolean',
        'is_insurance' => 'boolean',
        'is_free_followup' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function referenceDoctor()
    {
        return $this->belongsTo(ReferenceDoctor::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function patientClass()
    {
        return $this->belongsTo(PatientClass::class, 'class_id', 'class_id');
    }

    public function healthPackage()
    {
        return $this->belongsTo(HealthPackage::class, 'health_package_id', 'package_id');
    }

    public function cancelReason()
    {
        return $this->belongsTo(CancelReason::class, 'cancel_reason_id', 'cancel_reason_id');
    }

    public function previousVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'previous_visit_id', 'opd_id');
    }

    public function followupVisits()
    {
        return $this->hasMany(OpdVisit::class, 'previous_visit_id', 'opd_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function doctorGroup()
    {
        return $this->belongsTo(DoctorGroup::class, 'group_id', 'group_id');
    }

    public function entryCard()
    {
        return $this->belongsTo(EntryCard::class, 'entry_card_id', 'entry_card_id');
    }

    public function services()
    {
        return $this->hasMany(OpdVisitService::class, 'opd_id', 'opd_id');
    }

    public function investigations()
    {
        return $this->hasMany(OpdInvestigation::class, 'opd_id', 'opd_id');
    }

    public function vitals()
    {
        return $this->hasMany(PatientVital::class, 'opd_id', 'opd_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'opd_id', 'opd_id');
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class, 'opd_id', 'opd_id');
    }

    public function bill()
    {
        return $this->hasOne(Bill::class, 'opd_id', 'opd_id')
            ->orderBy('created_at', 'desc');
    }

    public function appointment()
    {
        return $this->hasOne(Appointment::class, 'opd_id', 'opd_id');
    }

    public function codingDiagnoses()
    {
        return $this->hasMany(CodingDiagnosis::class, 'reference_id', 'opd_id')
            ->where('reference_type', 'opd');
    }
}
