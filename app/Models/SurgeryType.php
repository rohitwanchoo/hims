<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryType extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'surgery_type_id';

    protected $fillable = [
        'hospital_id',
        'surgery_code',
        'surgery_name',
        'department_id',
        'specialty',
        'category',
        'complexity',
        'estimated_duration_mins',
        'base_charges',
        'surgeon_fee',
        'anesthesia_type',
        'requires_icu',
        'requires_blood',
        'is_active',
    ];

    protected $casts = [
        'base_charges' => 'decimal:2',
        'surgeon_fee' => 'decimal:2',
        'requires_icu' => 'boolean',
        'requires_blood' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
