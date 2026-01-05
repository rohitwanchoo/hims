<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class PatientClass extends Model
{
    use BelongsToHospital;

    protected $table = 'classes';
    protected $primaryKey = 'class_id';

    protected $fillable = [
        'hospital_id',
        'class_code',
        'class_name',
        'description',
        'client_id',
        'discount_ledger',
        'is_cashless',
        'apply_service_charges_on_cashless',
        'is_cashless_reimbursement',
        'is_copay',
        'copay_patient_percent',
        'copay_tpa_percent',
        'cashless_applicable_on',
        'pharmacy_cash',
        'apply_token',
        'ipd_for_new',
        'service_tax_applicable',
        'service_tax_on',
        'service_tax_bill_type',
        'grace_period_days',
        'prior_approval_required',
        'prior_approval_limit',
        'is_active',
    ];

    protected $casts = [
        'is_cashless' => 'boolean',
        'apply_service_charges_on_cashless' => 'boolean',
        'is_cashless_reimbursement' => 'boolean',
        'is_copay' => 'boolean',
        'copay_patient_percent' => 'decimal:2',
        'copay_tpa_percent' => 'decimal:2',
        'pharmacy_cash' => 'boolean',
        'apply_token' => 'boolean',
        'ipd_for_new' => 'boolean',
        'service_tax_applicable' => 'boolean',
        'prior_approval_required' => 'boolean',
        'prior_approval_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'class_id', 'class_id');
    }

    public function cashlessPriceLists()
    {
        return $this->hasMany(CashlessPriceList::class, 'class_id', 'class_id');
    }

    public function wardWiseCostAdditions()
    {
        return $this->hasMany(WardWiseCostAddition::class, 'class_id', 'class_id');
    }
}
