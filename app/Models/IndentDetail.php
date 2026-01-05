<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndentDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'indent_id',
        'item_id',
        'requested_quantity',
        'approved_quantity',
        'issued_quantity',
        'remarks',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'issued_quantity' => 'decimal:2',
    ];

    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class, 'indent_id', 'indent_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function getPendingQuantity(): float
    {
        $approved = $this->approved_quantity ?? $this->requested_quantity;
        return $approved - $this->issued_quantity;
    }

    public function isFullyIssued(): bool
    {
        return $this->getPendingQuantity() <= 0;
    }
}
