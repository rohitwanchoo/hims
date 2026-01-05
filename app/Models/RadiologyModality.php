<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyModality extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'modality_id';

    protected $fillable = [
        'hospital_id',
        'modality_code',
        'modality_name',
        'description',
        'room_number',
        'is_contrast_available',
        'default_tat_hours',
        'is_active',
    ];

    protected $casts = [
        'is_contrast_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tests(): HasMany
    {
        return $this->hasMany(RadiologyTest::class, 'modality_id', 'modality_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
