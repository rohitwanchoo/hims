<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorPatientAssignment extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'opd_id',
        'assigned_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }
}
