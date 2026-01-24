<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrugMaster extends Model
{
    protected $table = 'drug_masters';
    protected $primaryKey = 'drug_master_id';

    protected $fillable = [
        'hospital_id',
        'drug_type_id',
        'drug_name',
        'dose_time',
        'days',
        'quantity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'days' => 'integer',
        'quantity' => 'integer',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function drugType()
    {
        return $this->belongsTo(DrugType::class, 'drug_type_id', 'drug_type_id');
    }
}
