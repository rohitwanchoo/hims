<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class PharmacySale extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'prescription_id',
        'sale_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_status',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'prescription_id');
    }

    public function details()
    {
        return $this->hasMany(PharmacySaleDetail::class, 'sale_id', 'sale_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
