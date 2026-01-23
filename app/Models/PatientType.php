<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientType extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'patient_type_id';

    public function getRouteKeyName()
    {
        return 'patient_type_id';
    }

    protected $fillable = [
        'hospital_id',
        'patient_type_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'patient_type_id', 'patient_type_id');
    }
}
