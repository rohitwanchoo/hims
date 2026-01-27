<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class CostHead extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'cost_head_id';

    protected $fillable = [
        'hospital_id',
        'cost_head_code',
        'cost_head_name',
        'cost_head_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospitalServices()
    {
        return $this->hasMany(HospitalService::class, 'cost_head_id', 'cost_head_id');
    }
}
