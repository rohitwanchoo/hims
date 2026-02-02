<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class HospitalService extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'hospital_service_id';

    protected $fillable = [
        'hospital_id',
        'insurance_id',
        'cost_head_id',
        'service_name',
        'description',
        'from_date',
        'to_date',
        'base_price',
        'day_emergency_rate',
        'night_emergency_rate',
        'cashless_pricelist',
        'cl_rate',
        'cl_day_emergency_rate',
        'cl_night_emergency_rate',
        'price_unit',
        'is_active',
        'is_free_followup',
        'qty_rate_not_required',
        'gst_plan_id',
        'gst_above_amount',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'day_emergency_rate' => 'decimal:2',
        'night_emergency_rate' => 'decimal:2',
        'cl_rate' => 'decimal:2',
        'cl_day_emergency_rate' => 'decimal:2',
        'cl_night_emergency_rate' => 'decimal:2',
        'gst_above_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_free_followup' => 'boolean',
        'qty_rate_not_required' => 'boolean',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    protected $appends = ['insurance_company_name', 'cost_head_name', 'cost_head_type'];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_id', 'insurance_id');
    }

    public function costHead()
    {
        return $this->belongsTo(CostHead::class, 'cost_head_id', 'cost_head_id');
    }

    public function prices()
    {
        return $this->hasMany(HospitalServicePrice::class, 'hospital_service_id', 'hospital_service_id');
    }

    public function gstPlan()
    {
        return $this->belongsTo(GstPlan::class, 'gst_plan_id', 'gst_plan_id');
    }

    public function getInsuranceCompanyNameAttribute()
    {
        return $this->insuranceCompany ? $this->insuranceCompany->company_name : 'PRIVATE';
    }

    public function getCostHeadNameAttribute()
    {
        return $this->costHead ? $this->costHead->cost_head_name : null;
    }

    public function getCostHeadTypeAttribute()
    {
        return $this->costHead ? $this->costHead->cost_head_type : null;
    }
}
