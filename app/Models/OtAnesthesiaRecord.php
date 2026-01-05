<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtAnesthesiaRecord extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'anesthesia_record_id';

    protected $fillable = [
        'hospital_id',
        'procedure_id',
        'patient_id',
        'anesthetist_id',
        'pre_op_assessment',
        'asa_grade',
        'airway_assessment',
        'mallampati_score',
        'anesthesia_type',
        'anesthesia_technique',
        'agents_used',
        'monitoring_data',
        'intubation_details',
        'complications',
        'post_op_orders',
        'recovery_score',
    ];

    protected $casts = [
        'agents_used' => 'array',
        'monitoring_data' => 'array',
    ];

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(OtProcedure::class, 'procedure_id', 'procedure_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function anesthetist(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'anesthetist_id', 'doctor_id');
    }
}
