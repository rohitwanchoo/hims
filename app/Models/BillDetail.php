<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'bill_id',
        'item_type',
        'item_id',
        'cost_head_id',
        'item_name',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'item_id', 'service_id');
    }

    public function costHead()
    {
        return $this->belongsTo(CostHead::class, 'cost_head_id', 'cost_head_id');
    }
}
