<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'po_id';

    protected $fillable = [
        'hospital_id',
        'po_number',
        'supplier_id',
        'store_id',
        'po_date',
        'expected_delivery_date',
        'payment_terms',
        'delivery_address',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'po_id', 'po_id');
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class, 'po_id', 'po_id');
    }

    public static function generatePoNumber(int $hospitalId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $count = static::where('hospital_id', $hospitalId)
            ->whereDate('created_at', today())
            ->count();
        return $prefix . $date . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
