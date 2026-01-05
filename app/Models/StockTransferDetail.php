<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'transfer_id',
        'item_id',
        'stock_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'rate',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id', 'transfer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(ItemStock::class, 'stock_id', 'stock_id');
    }
}
