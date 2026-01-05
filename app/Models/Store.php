<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'store_id';

    protected $fillable = [
        'hospital_id',
        'store_code',
        'store_name',
        'store_type',
        'location',
        'in_charge_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inCharge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'in_charge_id', 'user_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ItemStock::class, 'store_id', 'store_id');
    }

    public function indentsFrom(): HasMany
    {
        return $this->hasMany(Indent::class, 'from_store_id', 'store_id');
    }

    public function indentsTo(): HasMany
    {
        return $this->hasMany(Indent::class, 'to_store_id', 'store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
