<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $primaryKey = 'prescription_id';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_id',
        'admission_id',
        'prescription_date',
        'diagnosis',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'prescription_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'visit_id', 'visit_id');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id', 'admission_id');
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id', 'prescription_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
