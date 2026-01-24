<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'hospital_id',
        'prefix_id',
        'first_name',
        'middle_name',
        'last_name',
        'pcd',
        'patient_name',
        'guardian_name',
        'relation',
        'dob',
        'age',
        'age_unit',
        'age_years',
        'age_months',
        'age_days',
        'gender',
        'gender_id',
        'marital_status',
        'marital_status_id',
        'blood_group',
        'blood_group_id',
        'patient_type_id',
        'mobile',
        'subscribe_sms',
        'subscribe_whatsapp',
        'subscribe_email',
        'phone',
        'patient_type',
        'email',
        'address',
        'area',
        'city',
        'district',
        'state',
        'country',
        // Permanent Address fields
        'permanent_address',
        'permanent_country_id',
        'permanent_state_id',
        'permanent_district_id',
        'permanent_city_id',
        'permanent_area_id',
        'permanent_pincode',
        'permanent_mobile',
        'permanent_email',
        // Current Address fields
        'same_as_permanent',
        'current_address',
        'current_country_id',
        'current_state_id',
        'current_district_id',
        'current_city_id',
        'current_area_id',
        'current_pincode',
        'current_mobile',
        'current_email',
        // Legacy address fields
        'permanent_city',
        'permanent_district',
        'permanent_state',
        'permanent_country',
        'pincode',
        'aadhaar_number',
        'pan_number',
        'relation_with_ip',
        'ip_name',
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
        'insurance_company_id',
        'insurance_policy_no',
        'class_id',
        'reference_doctor_id',
        'insurance_policy_number',
        'cashless_referral_no',
        'tpa_id',
        'registration_date',
        'photo',
        'documents',
        'is_active',
    ];

    protected $casts = [
        'dob' => 'date',
        'registration_date' => 'date',
        'subscribe_sms' => 'boolean',
        'subscribe_whatsapp' => 'boolean',
        'subscribe_email' => 'boolean',
        'same_as_permanent' => 'boolean',
        'is_bpl' => 'boolean',
        'is_foreigner' => 'boolean',
        'is_urgent' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['mobile_number', 'age', 'full_name', 'age_display'];

    public function prefix()
    {
        return $this->belongsTo(Prefix::class, 'prefix_id', 'prefix_id');
    }

    public function genderRelation()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'gender_id');
    }

    public function bloodGroupRelation()
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'blood_group_id');
    }

    public function patientTypeRelation()
    {
        return $this->belongsTo(PatientType::class, 'patient_type_id', 'patient_type_id');
    }

    public function maritalStatusRelation()
    {
        return $this->belongsTo(MaritalStatus::class, 'marital_status_id', 'marital_status_id');
    }

    public function insuranceCompanyRelation()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id', 'insurance_company_id');
    }

    // Permanent Address Relationships
    public function permanentCountry()
    {
        return $this->belongsTo(Country::class, 'permanent_country_id', 'country_id');
    }

    public function permanentState()
    {
        return $this->belongsTo(State::class, 'permanent_state_id', 'state_id');
    }

    public function permanentDistrict()
    {
        return $this->belongsTo(District::class, 'permanent_district_id', 'district_id');
    }

    public function permanentCity()
    {
        return $this->belongsTo(City::class, 'permanent_city_id', 'city_id');
    }

    public function permanentArea()
    {
        return $this->belongsTo(Area::class, 'permanent_area_id', 'area_id');
    }

    // Current Address Relationships
    public function currentCountry()
    {
        return $this->belongsTo(Country::class, 'current_country_id', 'country_id');
    }

    public function currentState()
    {
        return $this->belongsTo(State::class, 'current_state_id', 'state_id');
    }

    public function currentDistrict()
    {
        return $this->belongsTo(District::class, 'current_district_id', 'district_id');
    }

    public function currentCity()
    {
        return $this->belongsTo(City::class, 'current_city_id', 'city_id');
    }

    public function currentArea()
    {
        return $this->belongsTo(Area::class, 'current_area_id', 'area_id');
    }

    public function patientClass()
    {
        return $this->belongsTo(PatientClass::class, 'class_id', 'class_id');
    }

    public function referenceDoctor()
    {
        return $this->belongsTo(ReferenceDoctor::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function opdVisits()
    {
        return $this->hasMany(OpdVisit::class, 'patient_id', 'patient_id');
    }

    public function ipdAdmissions()
    {
        return $this->hasMany(IpdAdmission::class, 'patient_id', 'patient_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'patient_id');
    }

    /**
     * Doctors assigned to this patient
     */
    public function assignedDoctors()
    {
        return $this->belongsToMany(
            Doctor::class,
            'doctor_patient_assignments',
            'patient_id',
            'doctor_id'
        )
        ->withPivot(['assigned_date', 'status', 'opd_id'])
        ->withTimestamps();
    }

    /**
     * Latest OPD visit for this patient
     */
    public function latestOpdVisit()
    {
        return $this->hasOne(OpdVisit::class, 'patient_id', 'patient_id')
            ->latest('visit_date');
    }

    // Accessors for frontend compatibility
    public function getMobileNumberAttribute()
    {
        return $this->mobile ?? $this->permanent_mobile;
    }

    public function getAgeAttribute($value)
    {
        return $this->age_years ?? $value;
    }

    public function getFullNameAttribute()
    {
        if ($this->patient_name) {
            return $this->patient_name;
        }
        return trim(($this->first_name ?? '') . ' ' . ($this->middle_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getAgeDisplayAttribute()
    {
        if ($this->age_years) {
            return $this->age_years . ' yrs';
        }
        if ($this->age) {
            return $this->age . ' ' . ($this->age_unit ?? 'yrs');
        }
        return 'N/A';
    }
}