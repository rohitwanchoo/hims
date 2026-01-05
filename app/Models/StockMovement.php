<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'movement_id';

    protected $fillable = [
        'hospital_id',
        'store_id',
        'item_id',
        'stock_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'balance_after',
        'rate',
        'remarks',
        'created_by',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'rate' => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(ItemStock::class, 'stock_id', 'stock_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function isInward(): bool
    {
        return in_array($this->movement_type, ['receipt', 'transfer_in', 'adjustment_in', 'return']);
    }

    public function isOutward(): bool
    {
        return in_array($this->movement_type, ['issue', 'transfer_out', 'adjustment_out', 'damage', 'expired']);
    }
}
