<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'bill_id';

    protected $fillable = [
        'hospital_id',
        'bill_number',
        'patient_id',
        'visit_id',
        'admission_id',
        'bill_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'insurance_claim_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
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

    public function details()
    {
        return $this->hasMany(BillDetail::class, 'bill_id', 'bill_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'bill_id', 'bill_id');
    }

    public function insuranceClaim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id', 'claim_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
