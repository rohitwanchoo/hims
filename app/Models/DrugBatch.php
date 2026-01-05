<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class DrugBatch extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'batch_id';

    protected $fillable = [
        'hospital_id',
        'drug_id',
        'batch_number',
        'quantity',
        'current_quantity',
        'purchase_price',
        'selling_price',
        'manufacture_date',
        'expiry_date',
        'supplier',
        'received_date',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'current_quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drug_id', 'drug_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
