<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'hospital_id',
        'appointment_number',
        'patient_id',
        'doctor_id',
        'group_id',
        'department_id',
        'skill_set_id',
        'class_id',
        'reference_doctor_id',
        'appointment_date',
        'appointment_time',
        'appointment_type',
        'booking_mode',
        'service_type',
        'is_online',
        'priority',
        'slot_number',
        'slot_start_time',
        'slot_end_time',
        'duration_minutes',
        'reminder_sent',
        'booking_source',
        'status',
        'reason',
        'notes',
        'cancel_reason_id',
        'cancel_remarks',
        'cancelled_at',
        'cancelled_by',
        'transferred_from_id',
        'original_doctor_id',
        'opd_id',
        'checked_in_at',
        'arrived_at',
        'confirmed_at',
        'confirmed_by',
        'consultation_started_at',
        'consultation_ended_at',
        'created_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'is_online' => 'boolean',
        'reminder_sent' => 'boolean',
        'checked_in_at' => 'datetime',
        'arrived_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'consultation_started_at' => 'datetime',
        'consultation_ended_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function skillSet()
    {
        return $this->belongsTo(SkillSet::class, 'skill_set_id', 'skill_set_id');
    }

    public function patientClass()
    {
        return $this->belongsTo(PatientClass::class, 'class_id', 'class_id');
    }

    public function referenceDoctor()
    {
        return $this->belongsTo(ReferenceDoctor::class, 'reference_doctor_id', 'reference_doctor_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function doctorGroup()
    {
        return $this->belongsTo(DoctorGroup::class, 'group_id', 'group_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function cancelReason()
    {
        return $this->belongsTo(CancelReason::class, 'cancel_reason_id', 'cancel_reason_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'user_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'user_id');
    }

    public function originalDoctor()
    {
        return $this->belongsTo(Doctor::class, 'original_doctor_id', 'doctor_id');
    }

    public function transferredFrom()
    {
        return $this->belongsTo(Appointment::class, 'transferred_from_id', 'appointment_id');
    }

    /**
     * Scope for today's appointments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', now()->toDateString());
    }

    /**
     * Scope for upcoming appointments
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                     ->whereNotIn('status', ['completed', 'cancelled', 'no_show']);
    }

    /**
     * Scope for active (non-cancelled) appointments
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'no_show']);
    }

    /**
     * Check if appointment can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed']);
    }

    /**
     * Check if appointment can be transferred
     */
    public function canBeTransferred(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed']) && !$this->opd_id;
    }
}
