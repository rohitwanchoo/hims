<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFeedback extends Model
{
    use BelongsToHospital;

    protected $table = 'patient_feedback';
    protected $primaryKey = 'feedback_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'patient_user_id', 'feedback_type',
        'reference_type', 'reference_id', 'doctor_id', 'department_id',
        'overall_rating', 'cleanliness_rating', 'staff_rating',
        'wait_time_rating', 'doctor_rating', 'comments', 'suggestions',
        'is_anonymous', 'is_addressed', 'response', 'responded_by', 'responded_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_addressed' => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function patientUser(): BelongsTo
    {
        return $this->belongsTo(PatientUser::class, 'patient_user_id', 'patient_user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by', 'user_id');
    }

    public function getAverageRating(): float
    {
        $ratings = array_filter([
            $this->overall_rating,
            $this->cleanliness_rating,
            $this->staff_rating,
            $this->wait_time_rating,
            $this->doctor_rating,
        ]);
        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
    }
}
