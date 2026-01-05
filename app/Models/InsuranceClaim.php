<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    protected $primaryKey = 'claim_id';

    protected $fillable = [
        'bill_id',
        'company_id',
        'claim_number',
        'claim_date',
        'claim_amount',
        'approved_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'claim_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id', 'company_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
