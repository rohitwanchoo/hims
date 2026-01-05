<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptNote extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'grn_id';

    protected $fillable = [
        'hospital_id',
        'grn_number',
        'po_id',
        'supplier_id',
        'store_id',
        'grn_date',
        'invoice_number',
        'invoice_date',
        'invoice_amount',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'status',
        'verified_by',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'grn_date' => 'date',
        'invoice_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

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
        return $this->hasMany(GrnDetail::class, 'grn_id', 'grn_id');
    }

    public static function generateGrnNumber(int $hospitalId): string
    {
        $prefix = 'GRN';
        $date = now()->format('Ymd');
        $count = static::where('hospital_id', $hospitalId)
            ->whereDate('created_at', today())
            ->count();
        return $prefix . $date . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
