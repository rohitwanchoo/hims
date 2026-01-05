<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class DoctorOpdRate extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'rate_id';

    protected $fillable = [
        'hospital_id',
        'doctor_id',
        'class_id',
        'visit_type',
        'charge_type',
        'rate',
        'free_followup_rate',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'free_followup_rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function patientClass()
    {
        return $this->belongsTo(PatientClass::class, 'class_id', 'class_id');
    }

    /**
     * Get applicable rate for a doctor, visit type, and optionally class
     */
    public static function getRate($doctorId, $visitType = 'new', $chargeType = 'normal', $classId = null)
    {
        $query = self::where('doctor_id', $doctorId)
            ->where('visit_type', $visitType)
            ->where('charge_type', $chargeType)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now()->toDateString());
            });

        // First try to find class-specific rate
        if ($classId) {
            $classRate = (clone $query)->where('class_id', $classId)->first();
            if ($classRate) return $classRate->rate;
        }

        // Fall back to general rate (no class)
        $generalRate = $query->whereNull('class_id')->first();
        return $generalRate ? $generalRate->rate : null;
    }
}
