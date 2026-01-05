<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Drug extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'drug_id';

    protected $fillable = [
        'hospital_id',
        'category_id',
        'schedule_id',
        'drug_name',
        'drug_code',
        'generic_name',
        'manufacturer',
        'unit',
        'purchase_price',
        'selling_price',
        'reorder_level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(DrugCategory::class, 'category_id', 'category_id');
    }

    public function schedule()
    {
        return $this->belongsTo(DrugSchedule::class, 'schedule_id', 'schedule_id');
    }

    public function batches()
    {
        return $this->hasMany(DrugBatch::class, 'drug_id', 'drug_id');
    }

    public function getCurrentStockAttribute()
    {
        return $this->batches()->where('expiry_date', '>', now())->sum('current_quantity');
    }
}
