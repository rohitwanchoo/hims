<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'grn_id',
        'item_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'free_quantity',
        'purchase_rate',
        'mrp',
        'discount_percent',
        'tax_percent',
        'amount',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'free_quantity' => 'decimal:2',
        'purchase_rate' => 'decimal:2',
        'mrp' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id', 'grn_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function getTotalQuantity(): float
    {
        return $this->quantity + $this->free_quantity;
    }
}
