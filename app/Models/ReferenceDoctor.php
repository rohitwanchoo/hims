<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceDoctor extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'reference_doctor_id';

    protected $fillable = [
        'hospital_id',
        'prefix_id',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'gender_id',
        'blood_group_id',
        'qualification_id',
        'department_id',
        'qualification',
        'skill_set',
        'specialization',
        'registration_no',
        'practice_no',
        'dob',
        'hospital_name',
        'group_name',
        'clinic_name',
        'address',
        'city',
        'state',
        'pincode',
        'mobile',
        'phone',
        'email',
        'commission_percent',
        // Residence Address
        'res_address_line1',
        'res_address_line2',
        'res_country_id',
        'res_state_id',
        'res_district_id',
        'res_city_id',
        'res_pincode',
        'res_fax',
        'res_tel1',
        'res_tel2',
        'res_mobile',
        'res_email',
        'res_website',
        // Clinic Address
        'clinic_address_line1',
        'clinic_address_line2',
        'clinic_country_id',
        'clinic_state_id',
        'clinic_district_id',
        'clinic_city_id',
        'clinic_pincode',
        'clinic_fax',
        'clinic_tel1',
        'clinic_tel2',
        'clinic_mobile',
        'clinic_email',
        'clinic_website',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dob' => 'date',
        'commission_percent' => 'decimal:2'
    ];

    protected $appends = ['doctor_name'];

    public function getRouteKeyName()
    {
        return 'reference_doctor_id';
    }

    // Computed full name
    public function getDoctorNameAttribute()
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name
        ]);
        return implode(' ', $parts) ?: $this->full_name;
    }

    // Relationships
    public function patients()
    {
        return $this->hasMany(Patient::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function prefix()
    {
        return $this->belongsTo(Prefix::class, 'prefix_id', 'prefix_id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'gender_id');
    }

    public function bloodGroup()
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'blood_group_id');
    }

    public function qualificationMaster()
    {
        return $this->belongsTo(Qualification::class, 'qualification_id', 'qualification_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    // Residence Address relationships
    public function resCountry()
    {
        return $this->belongsTo(Country::class, 'res_country_id', 'country_id');
    }

    public function resState()
    {
        return $this->belongsTo(State::class, 'res_state_id', 'state_id');
    }

    public function resDistrict()
    {
        return $this->belongsTo(District::class, 'res_district_id', 'district_id');
    }

    public function resCity()
    {
        return $this->belongsTo(City::class, 'res_city_id', 'city_id');
    }

    // Clinic Address relationships
    public function clinicCountry()
    {
        return $this->belongsTo(Country::class, 'clinic_country_id', 'country_id');
    }

    public function clinicState()
    {
        return $this->belongsTo(State::class, 'clinic_state_id', 'state_id');
    }

    public function clinicDistrict()
    {
        return $this->belongsTo(District::class, 'clinic_district_id', 'district_id');
    }

    public function clinicCity()
    {
        return $this->belongsTo(City::class, 'clinic_city_id', 'city_id');
    }
}
