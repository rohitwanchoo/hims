<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class ReferenceDoctor extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'reference_doctor_id';

    protected $fillable = [
        'hospital_id',
        'doctor_code',
        'full_name',
        'qualification',
        'skill_set',
        'registration_no',
        'hospital_name',
        'group_name',
        'address',
        'city',
        'state',
        'pincode',
        'mobile',
        'phone',
        'email',
        'commission_percent',
        'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function opdVisits()
    {
        return $this->hasMany(OpdVisit::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function ipdAdmissions()
    {
        return $this->hasMany(IpdAdmission::class, 'reference_doctor_id', 'reference_doctor_id');
    }
}
