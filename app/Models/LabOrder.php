<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'order_id';

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'doctor_id',
        'visit_id',
        'admission_id',
        'order_date',
        'status',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
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

    public function details()
    {
        return $this->hasMany(LabOrderDetail::class, 'order_id', 'order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
