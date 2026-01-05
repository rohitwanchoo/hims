<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class WardWiseCostAddition extends Model
{
    use BelongsToHospital;

    protected $table = 'ward_wise_cost_additions';
    protected $primaryKey = 'wwca_id';

    protected $fillable = [
        'hospital_id',
        'class_id',
        'service_id',
        'ward_id',
        'rate',
        'rate_type',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function patientClass()
    {
        return $this->belongsTo(PatientClass::class, 'class_id', 'class_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }
}
