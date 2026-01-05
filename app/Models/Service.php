<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'service_id';

    protected $fillable = [
        'hospital_id',
        'service_code',
        'service_name',
        'ledger_name',
        'department_id',
        'service_type',
        'sub_service_type',
        'is_health_checkup',
        'apply_service_charges',
        'use_for_service_bill',
        'is_special_service',
        'service_used_for',
        'is_followup_service',
        'is_free_followup',
        'service_tax_applicable',
        'service_tax_plan',
        'rate',
        'night_emergency_rate',
        'day_emergency_rate',
        'is_premium_service',
        'use_for_srn',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'night_emergency_rate' => 'decimal:2',
        'day_emergency_rate' => 'decimal:2',
        'is_health_checkup' => 'boolean',
        'apply_service_charges' => 'boolean',
        'use_for_service_bill' => 'boolean',
        'is_special_service' => 'boolean',
        'is_followup_service' => 'boolean',
        'is_free_followup' => 'boolean',
        'service_tax_applicable' => 'boolean',
        'is_premium_service' => 'boolean',
        'use_for_srn' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function cashlessPriceLists()
    {
        return $this->hasMany(CashlessPriceList::class, 'service_id', 'service_id');
    }

    public function wardWiseCostAdditions()
    {
        return $this->hasMany(WardWiseCostAddition::class, 'service_id', 'service_id');
    }
}
