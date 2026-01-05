<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'hospital_id',
        'item_code',
        'item_name',
        'generic_name',
        'category_id',
        'item_type',
        'unit_of_measure',
        'pack_size',
        'hsn_code',
        'gst_percent',
        'manufacturer',
        'reorder_level',
        'reorder_quantity',
        'minimum_stock',
        'maximum_stock',
        'is_batch_tracked',
        'is_expiry_tracked',
        'is_serialized',
        'storage_conditions',
        'is_active',
    ];

    protected $casts = [
        'gst_percent' => 'decimal:2',
        'is_batch_tracked' => 'boolean',
        'is_expiry_tracked' => 'boolean',
        'is_serialized' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id', 'category_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ItemStock::class, 'item_id', 'item_id');
    }

    public function getTotalStock(int $storeId = null): float
    {
        $query = $this->stocks();
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        return $query->sum('quantity');
    }

    public function isLowStock(int $storeId = null): bool
    {
        return $this->getTotalStock($storeId) <= $this->reorder_level;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query, int $storeId = null)
    {
        return $query->whereHas('stocks', function ($q) use ($storeId) {
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        })->whereRaw('(SELECT SUM(quantity) FROM item_stock WHERE item_stock.item_id = items.item_id) <= reorder_level');
    }
}
