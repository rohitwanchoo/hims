<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class CashlessPriceList extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'price_list_id';

    protected $fillable = [
        'hospital_id',
        'class_id',
        'service_id',
        'opd_rate',
        'ipd_rate',
        'day_emergency_rate',
        'night_emergency_rate',
        'is_approval_required',
        'is_not_applicable',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'opd_rate' => 'decimal:2',
        'ipd_rate' => 'decimal:2',
        'day_emergency_rate' => 'decimal:2',
        'night_emergency_rate' => 'decimal:2',
        'is_approval_required' => 'boolean',
        'is_not_applicable' => 'boolean',
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
}
