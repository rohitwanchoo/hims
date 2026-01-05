<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'hospital_id',
        'pcd',
        'patient_name',
        'guardian_name',
        'relation',
        'dob',
        'age',
        'age_unit',
        'gender',
        'marital_status',
        'blood_group',
        'mobile',
        'subscribe_sms',
        'phone',
        'email',
        'address',
        'area',
        'city',
        'district',
        'state',
        'country',
        'pincode',
        'permanent_address',
        'permanent_city',
        'permanent_district',
        'permanent_state',
        'permanent_country',
        'permanent_pincode',
        'aadhaar_number',
        'pan_number',
        'occupation',
        'nationality',
        'is_bpl',
        'is_foreigner',
        'is_urgent',
        'religion',
        'category',
        'charges_type',
        'allergies',
        'medical_history',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'insurance_id',
        'insurance_policy_number',
        'cashless_referral_no',
        'tpa_id',
        'relation_with_ip',
        'ip_name',
        'class_id',
        'reference_doctor_id',
        'registration_date',
        'photo',
        'documents',
        'is_active',
    ];

    protected $casts = [
        'dob' => 'date',
        'registration_date' => 'date',
        'subscribe_sms' => 'boolean',
        'is_bpl' => 'boolean',
        'is_foreigner' => 'boolean',
        'is_urgent' => 'boolean',
        'is_active' => 'boolean',
        'documents' => 'array',
    ];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_id', 'insurance_id');
    }

    public function patientClass()
    {
        return $this->belongsTo(PatientClass::class, 'class_id', 'class_id');
    }

    public function referenceDoctor()
    {
        return $this->belongsTo(ReferenceDoctor::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'patient_id');
    }

    public function opdVisits()
    {
        return $this->hasMany(OpdVisit::class, 'patient_id', 'patient_id');
    }

    public function ipdAdmissions()
    {
        return $this->hasMany(IpdAdmission::class, 'patient_id', 'patient_id');
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class, 'patient_id', 'patient_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'patient_id', 'patient_id');
    }

    public function vaccinations()
    {
        return $this->hasMany(PatientVaccination::class, 'patient_id', 'patient_id');
    }
}
