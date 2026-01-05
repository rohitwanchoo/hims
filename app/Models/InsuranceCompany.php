<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    protected $primaryKey = 'company_id';

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'insurance_company_id', 'company_id');
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class, 'company_id', 'company_id');
    }
}
