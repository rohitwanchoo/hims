<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'hospital_id',
        'category_code',
        'category_name',
        'parent_category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'parent_category_id', 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ItemCategory::class, 'parent_category_id', 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id', 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_category_id');
    }
}
