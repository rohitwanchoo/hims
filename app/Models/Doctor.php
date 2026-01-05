<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'doctor_id';

    protected $fillable = [
        'hospital_id',
        'doctor_code',
        'full_name',
        'qualification',
        'specialization',
        'department_id',
        'mobile',
        'email',
        'consultation_fee',
        'opd_available',
        'ipd_available',
        'is_active',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'opd_available' => 'boolean',
        'ipd_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id', 'doctor_id');
    }

    public function opdVisits()
    {
        return $this->hasMany(OpdVisit::class, 'doctor_id', 'doctor_id');
    }

    public function ipdAdmissions()
    {
        return $this->hasMany(IpdAdmission::class, 'doctor_id', 'doctor_id');
    }
}
