<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientVaccination extends Model
{
    protected $primaryKey = 'patient_vaccination_id';

    protected $fillable = [
        'patient_id',
        'vaccination_id',
        'scheduled_date',
        'administered_date',
        'batch_number',
        'administered_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'administered_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function vaccination()
    {
        return $this->belongsTo(Vaccination::class, 'vaccination_id', 'vaccination_id');
    }
}
