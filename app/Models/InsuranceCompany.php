<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'insurance_id';

    protected $fillable = [
        'hospital_id',
        'company_name',
        'company_code',
        'address',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'website',
        'discount_percent',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_percent' => 'decimal:2'
    ];

    public function getRouteKeyName()
    {
        return 'insurance_id';
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'insurance_company_id', 'insurance_id');
    }
}
