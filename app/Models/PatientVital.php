<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientVital extends Model
{
    protected $primaryKey = 'vital_id';

    protected $fillable = [
        'patient_id',
        'visit_id',
        'admission_id',
        'recorded_at',
        'temperature',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'height',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:1',
        'blood_pressure_systolic' => 'integer',
        'blood_pressure_diastolic' => 'integer',
        'pulse_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'oxygen_saturation' => 'integer',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'visit_id', 'visit_id');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id', 'admission_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }
}
