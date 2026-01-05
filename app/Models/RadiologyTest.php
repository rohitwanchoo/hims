<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyTest extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'radiology_test_id';

    protected $fillable = [
        'hospital_id',
        'modality_id',
        'test_code',
        'test_name',
        'body_part',
        'laterality',
        'cpt_code',
        'rate',
        'contrast_required',
        'contrast_rate',
        'preparation_instructions',
        'consent_required',
        'tat_hours',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'contrast_rate' => 'decimal:2',
        'contrast_required' => 'boolean',
        'consent_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function modality(): BelongsTo
    {
        return $this->belongsTo(RadiologyModality::class, 'modality_id', 'modality_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
