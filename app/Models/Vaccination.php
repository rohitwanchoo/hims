<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'vaccination_id';

    protected $fillable = [
        'hospital_id',
        'vaccine_code',
        'vaccine_name',
        'manufacturer',
        'schedule_value',
        'schedule_type',
        'schedule_text',
        'dose_number',
        'total_doses',
        'description',
        'instructions',
        'rate',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function patientVaccinations()
    {
        return $this->hasMany(PatientVaccination::class, 'vaccination_id', 'vaccination_id');
    }
}
