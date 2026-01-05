<?php

namespace App\Traits;

use App\Models\Hospital;
use App\Scopes\HospitalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToHospital
{
    protected static function bootBelongsToHospital(): void
    {
        // Add global scope to automatically filter by hospital
        static::addGlobalScope(new HospitalScope);

        // Automatically set hospital_id when creating
        static::creating(function ($model) {
            if (empty($model->hospital_id) && app()->bound('current_hospital_id')) {
                $model->hospital_id = app('current_hospital_id');
            }
        });
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }
}
