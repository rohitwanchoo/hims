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

    /**
     * Patients assigned to this doctor
     */
    public function assignedPatients()
    {
        return $this->belongsToMany(
            Patient::class,
            'doctor_patient_assignments',
            'doctor_id',
            'patient_id'
        )
        ->withPivot(['assigned_date', 'status', 'opd_id'])
        ->withTimestamps()
        ->wherePivot('status', 'active');
    }

    /**
     * All patient assignments (including historical)
     */
    public function patientAssignments()
    {
        return $this->hasMany(DoctorPatientAssignment::class, 'doctor_id', 'doctor_id');
    }

    /**
     * Today's OPD visits
     */
    public function todaysVisits()
    {
        return $this->opdVisits()
            ->whereDate('visit_date', today())
            ->orderBy('token_number');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
