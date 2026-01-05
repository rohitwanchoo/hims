<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacySaleDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'sale_id',
        'drug_id',
        'batch_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id', 'sale_id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drug_id', 'drug_id');
    }

    public function batch()
    {
        return $this->belongsTo(DrugBatch::class, 'batch_id', 'batch_id');
    }
}
