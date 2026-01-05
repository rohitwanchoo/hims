<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'hospital_id',
        'supplier_code',
        'supplier_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'gst_number',
        'pan_number',
        'payment_terms',
        'credit_limit',
        'credit_days',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'supplier_id');
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class, 'supplier_id', 'supplier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
