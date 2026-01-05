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
        'bill_id',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_id',
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

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}
