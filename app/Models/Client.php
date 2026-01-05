<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'client_id';

    protected $fillable = [
        'hospital_id',
        'client_code',
        'client_name',
        'contact_person',
        'ledger_name',
        'address',
        'city',
        'state',
        'pincode',
        'mobile',
        'phone',
        'email',
        'website',
        'category',
        'rate_based_on',
        'rate_adjustment_percent',
        'discount_percent',
        'credit_limit',
        'credit_days',
        'is_active',
    ];

    protected $casts = [
        'rate_adjustment_percent' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function classes()
    {
        return $this->hasMany(PatientClass::class, 'client_id', 'client_id');
    }
}
