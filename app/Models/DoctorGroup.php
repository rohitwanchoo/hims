<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class DoctorGroup extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'group_id';

    protected $fillable = [
        'hospital_id',
        'group_code',
        'group_name',
        'group_type',
        'department_id',
        'head_doctor_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function headDoctor()
    {
        return $this->belongsTo(Doctor::class, 'head_doctor_id', 'doctor_id');
    }

    public function members()
    {
        return $this->hasMany(DoctorGroupMember::class, 'group_id', 'group_id');
    }

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_group_members', 'group_id', 'doctor_id', 'group_id', 'doctor_id')
            ->withPivot(['role', 'can_consult', 'is_active'])
            ->wherePivot('is_active', true);
    }

    public function consultingDoctors()
    {
        return $this->doctors()->wherePivot('can_consult', true);
    }

    public function opdVisits()
    {
        return $this->hasMany(OpdVisit::class, 'group_id', 'group_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'group_id', 'group_id');
    }
}
