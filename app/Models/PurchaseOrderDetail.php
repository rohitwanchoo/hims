<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'po_id',
        'item_id',
        'quantity',
        'rate',
        'discount_percent',
        'tax_percent',
        'amount',
        'received_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'amount' => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function getPendingQuantity(): float
    {
        return $this->quantity - $this->received_quantity;
    }
}
