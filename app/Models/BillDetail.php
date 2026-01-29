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
        'service_date',
        'doctor_id',
        'quantity',
        'rate',
        'discount_amount',
        'tax_amount',
        'amount',
    ];

    protected $casts = [
        'service_date' => 'datetime',
        'quantity' => 'integer',
        'rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['unit_price', 'bill_detail_id'];

    // Add accessor for backwards compatibility with frontend
    public function getUnitPriceAttribute()
    {
        return $this->rate;
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['rate'] = $value;
    }

    public function getBillDetailIdAttribute()
    {
        return $this->detail_id;
    }

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

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
