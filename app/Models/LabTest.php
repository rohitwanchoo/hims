<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'test_id';

    protected $fillable = [
        'hospital_id',
        'test_code',
        'test_name',
        'category_id',
        'test_category',
        'rate',
        'sample_type',
        'normal_range',
        'unit',
        'tat_hours',
        'method',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'tat_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(LabCategory::class, 'category_id', 'category_id');
    }
}
