<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAppointmentRequest extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'patient_user_id',
        'department_id', 'doctor_id', 'preferred_date', 'preferred_time_slot',
        'alternate_date', 'reason', 'status', 'appointment_id',
        'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'alternate_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function patientUser(): BelongsTo
    {
        return $this->belongsTo(PatientUser::class, 'patient_user_id', 'patient_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
