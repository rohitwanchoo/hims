<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrugType extends Model
{
    protected $table = 'drug_types';
    protected $primaryKey = 'drug_type_id';

    protected $fillable = [
        'hospital_id',
        'type_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function drugs()
    {
        return $this->hasMany(Drug::class, 'drug_type_id', 'drug_type_id');
    }
}
