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
        'opd_id',
        'ipd_id',
        'bill_date',
        'bill_type',
        'payment_mode',
        'insurance_company',
        'policy_number',
        'subtotal',
        'discount_amount',
        'discount_percent',
        'tax_amount',
        'adjustment',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'insurance_claim_id',
        'approved_amount',
        'copay_amount',
        'insurance_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'adjustment' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'copay_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function details()
    {
        return $this->hasMany(BillDetail::class, 'bill_id', 'bill_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'bill_id', 'bill_id');
    }

    public function history()
    {
        return $this->hasMany(BillHistory::class, 'bill_id', 'bill_id');
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
