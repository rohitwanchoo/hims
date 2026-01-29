<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'hospital_id',
        'payment_number',
        'bill_id',
        'patient_id',
        'amount',
        'payment_date',
        'payment_mode',
        'reference_number',
        'transaction_id',
        'status',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}
