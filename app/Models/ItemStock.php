<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemStock extends Model
{
    use BelongsToHospital;

    protected $table = 'item_stock';
    protected $primaryKey = 'stock_id';

    protected $fillable = [
        'hospital_id',
        'store_id',
        'item_id',
        'batch_number',
        'expiry_date',
        'serial_number',
        'quantity',
        'reserved_quantity',
        'purchase_rate',
        'mrp',
        'selling_rate',
        'supplier_id',
        'received_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_date' => 'date',
        'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'purchase_rate' => 'decimal:2',
        'mrp' => 'decimal:2',
        'selling_rate' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function getAvailableQuantity(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }
}
