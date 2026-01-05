<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class IpdNursingChart extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'chart_id';

    protected $fillable = [
        'hospital_id',
        'ipd_id',
        'nurse_id',
        'chart_date',
        'shift',
        // Vitals
        'bp_systolic',
        'bp_diastolic',
        'pulse',
        'temperature',
        'spo2',
        'respiratory_rate',
        'blood_sugar',
        // Intake/Output
        'oral_intake_ml',
        'iv_intake_ml',
        'urine_output_ml',
        'drain_output_ml',
        'vomit_ml',
        // Assessments
        'general_condition',
        'pain_assessment',
        'wound_assessment',
        'iv_site_assessment',
        'nursing_notes',
        'medications_given',
        'patient_response',
    ];

    protected $casts = [
        'chart_date' => 'date',
        'bp_systolic' => 'integer',
        'bp_diastolic' => 'integer',
        'pulse' => 'integer',
        'temperature' => 'decimal:1',
        'spo2' => 'integer',
        'respiratory_rate' => 'integer',
        'blood_sugar' => 'decimal:1',
        'oral_intake_ml' => 'integer',
        'iv_intake_ml' => 'integer',
        'urine_output_ml' => 'integer',
        'drain_output_ml' => 'integer',
        'vomit_ml' => 'integer',
    ];

    protected $appends = ['blood_pressure', 'total_intake', 'total_output', 'fluid_balance'];

    public function getBloodPressureAttribute()
    {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return $this->bp_systolic . '/' . $this->bp_diastolic;
        }
        return null;
    }

    public function getTotalIntakeAttribute()
    {
        return ($this->oral_intake_ml ?? 0) + ($this->iv_intake_ml ?? 0);
    }

    public function getTotalOutputAttribute()
    {
        return ($this->urine_output_ml ?? 0) + ($this->drain_output_ml ?? 0) + ($this->vomit_ml ?? 0);
    }

    public function getFluidBalanceAttribute()
    {
        return $this->total_intake - $this->total_output;
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id', 'user_id');
    }

    // Scopes
    public function scopeByShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('chart_date', today());
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('chart_date', [$startDate, $endDate]);
    }
}
