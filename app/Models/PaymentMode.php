<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'payment_mode_id';

    protected $fillable = [
        'hospital_id',
        'mode_code',
        'mode_name',
        'description',
        'has_financial_details',
        'use_for_refund',
        'is_title_mandatory',
        'value_type',
        'value_max_length',
        'post_charges_applicable',
        'post_charge_percent',
        'is_active',
    ];

    protected $casts = [
        'has_financial_details' => 'boolean',
        'use_for_refund' => 'boolean',
        'is_title_mandatory' => 'boolean',
        'post_charges_applicable' => 'boolean',
        'post_charge_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(PaymentModeDetail::class, 'payment_mode_id', 'payment_mode_id');
    }
}
