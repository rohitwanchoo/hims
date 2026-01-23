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
        'order_number',
        'patient_id',
        'opd_id',
        'ipd_id',
        'order_date',
        'referred_by',
        'priority',
        'clinical_notes',
        'total_amount',
        'status',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(Doctor::class, 'referred_by', 'doctor_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'visit_id');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'admission_id');
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
