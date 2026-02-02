<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashlessPriceHistory extends Model
{
    protected $table = 'cashless_price_history';
    protected $primaryKey = 'history_id';
    public $timestamps = false;

    protected $fillable = [
        'hospital_service_id',
        'hospital_id',
        'service_name',
        'insurance_company_name',
        'old_cl_rate',
        'old_cl_day_emergency_rate',
        'old_cl_night_emergency_rate',
        'old_from_date',
        'old_to_date',
        'new_cl_rate',
        'new_cl_day_emergency_rate',
        'new_cl_night_emergency_rate',
        'new_from_date',
        'new_to_date',
        'updated_by',
        'updated_by_name',
        'updated_at',
    ];

    protected $casts = [
        'old_cl_rate' => 'decimal:2',
        'old_cl_day_emergency_rate' => 'decimal:2',
        'old_cl_night_emergency_rate' => 'decimal:2',
        'new_cl_rate' => 'decimal:2',
        'new_cl_day_emergency_rate' => 'decimal:2',
        'new_cl_night_emergency_rate' => 'decimal:2',
        'old_from_date' => 'date',
        'old_to_date' => 'date',
        'new_from_date' => 'date',
        'new_to_date' => 'date',
        'updated_at' => 'datetime',
    ];

    public function hospitalService()
    {
        return $this->belongsTo(HospitalService::class, 'hospital_service_id', 'hospital_service_id');
    }
}
