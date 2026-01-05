<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OtSchedule extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'hospital_id',
        'ot_id',
        'ipd_id',
        'patient_id',
        'surgery_type_id',
        'surgery_name',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'estimated_duration_mins',
        'priority',
        'pre_op_diagnosis',
        'planned_procedure',
        'surgeon_id',
        'assistant_surgeon_id',
        'anesthetist_id',
        'anesthesia_type',
        'special_equipment',
        'blood_requirement',
        'pre_op_checklist_complete',
        'consent_obtained',
        'status',
        'postpone_reason',
        'cancel_reason',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'pre_op_checklist_complete' => 'boolean',
        'consent_obtained' => 'boolean',
    ];

    public function operationTheater(): BelongsTo
    {
        return $this->belongsTo(OperationTheater::class, 'ot_id', 'ot_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function ipdAdmission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class, 'surgery_type_id', 'surgery_type_id');
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id', 'doctor_id');
    }

    public function assistantSurgeon(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'assistant_surgeon_id', 'doctor_id');
    }

    public function anesthetist(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'anesthetist_id', 'doctor_id');
    }

    public function procedure(): HasOne
    {
        return $this->hasOne(OtProcedure::class, 'schedule_id', 'schedule_id');
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(OtPreOpChecklist::class, 'schedule_id', 'schedule_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->where('scheduled_date', today());
    }
}
