<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationTheater extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'ot_id';

    protected $fillable = [
        'hospital_id',
        'ot_code',
        'ot_name',
        'ot_type',
        'floor',
        'capacity',
        'has_laminar_flow',
        'has_c_arm',
        'has_laparoscopy',
        'charges_per_hour',
        'is_active',
    ];

    protected $casts = [
        'has_laminar_flow' => 'boolean',
        'has_c_arm' => 'boolean',
        'has_laparoscopy' => 'boolean',
        'charges_per_hour' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(OtSchedule::class, 'ot_id', 'ot_id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(OtProcedure::class, 'ot_id', 'ot_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAvailable(\DateTime $date, string $startTime, string $endTime): bool
    {
        return !$this->schedules()
            ->where('scheduled_date', $date->format('Y-m-d'))
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('scheduled_start_time', [$startTime, $endTime])
                    ->orWhereBetween('scheduled_end_time', [$startTime, $endTime]);
            })
            ->exists();
    }
}
