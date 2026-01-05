<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class IpdAdmission extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'ipd_id';

    protected $fillable = [
        'hospital_id',
        'ipd_number',
        'patient_id',
        'admission_date',
        'admission_time',
        'admission_type',
        'admission_source',
        'opd_id',
        'department_id',
        'admitting_doctor_id',
        'treating_doctor_id',
        'consultant_doctor_id',
        'ward_id',
        'bed_id',
        'diagnosis_at_admission',
        'provisional_diagnosis',
        'icd_code',
        'admission_notes',
        'treatment_plan',
        // MLC/Emergency
        'mlc_case',
        'mlc_number',
        'is_emergency',
        'police_station',
        'brought_by',
        'referral_from',
        'expected_los',
        // Insurance
        'insurance_applicable',
        'insurance_id',
        'class_id',
        'reference_doctor_id',
        'insurance_approval_number',
        'tpa_name',
        'approved_amount',
        'pre_auth_amount',
        'scheme_type',
        // Billing
        'total_charges',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'advance_amount',
        'paid_amount',
        'due_amount',
        'credit_limit',
        'bill_id',
        // Attendant
        'attendant_name',
        'attendant_relation',
        'attendant_mobile',
        // Status and Discharge
        'status',
        'discharge_date',
        'discharge_time',
        'discharge_type',
        'discharged_by',
        'discharge_summary',
        'final_diagnosis',
        'condition_at_discharge',
        'followup_advice',
        'followup_date',
        // Death
        'death_date',
        'death_time',
        'cause_of_death',
        // Other
        'cancel_reason_id',
        'created_by',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'followup_date' => 'date',
        'death_date' => 'date',
        'mlc_case' => 'boolean',
        'is_emergency' => 'boolean',
        'insurance_applicable' => 'boolean',
        'total_charges' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'pre_auth_amount' => 'decimal:2',
    ];

    protected $appends = ['los_days', 'admission_datetime', 'patient_name', 'doctor_name', 'ward_name', 'bed_number'];

    // Accessor for Length of Stay
    public function getLosDaysAttribute()
    {
        $endDate = $this->discharge_date ?? Carbon::today();
        return Carbon::parse($this->admission_date)->diffInDays($endDate);
    }

    public function getAdmissionDatetimeAttribute()
    {
        return $this->admission_date->format('Y-m-d') . ' ' . ($this->admission_time ?? '00:00:00');
    }

    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->full_name : null;
    }

    public function getDoctorNameAttribute()
    {
        return $this->treatingDoctor ? $this->treatingDoctor->doctor_name : null;
    }

    public function getWardNameAttribute()
    {
        return $this->ward ? $this->ward->ward_name : null;
    }

    public function getBedNumberAttribute()
    {
        return $this->bed ? $this->bed->bed_number : null;
    }

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function admittingDoctor()
    {
        return $this->belongsTo(Doctor::class, 'admitting_doctor_id', 'doctor_id');
    }

    public function treatingDoctor()
    {
        return $this->belongsTo(Doctor::class, 'treating_doctor_id', 'doctor_id');
    }

    public function consultantDoctor()
    {
        return $this->belongsTo(Doctor::class, 'consultant_doctor_id', 'doctor_id');
    }

    public function referenceDoctor()
    {
        return $this->belongsTo(Doctor::class, 'reference_doctor_id', 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class, 'bed_id', 'bed_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function dischargedByUser()
    {
        return $this->belongsTo(User::class, 'discharged_by', 'user_id');
    }

    // Related records
    public function progressNotes()
    {
        return $this->hasMany(IpdProgressNote::class, 'ipd_id', 'ipd_id')->orderBy('note_date', 'desc')->orderBy('note_time', 'desc');
    }

    public function nursingCharts()
    {
        return $this->hasMany(IpdNursingChart::class, 'ipd_id', 'ipd_id')->orderBy('chart_date', 'desc');
    }

    public function services()
    {
        return $this->hasMany(IpdService::class, 'ipd_id', 'ipd_id')->orderBy('service_date', 'desc');
    }

    public function investigations()
    {
        return $this->hasMany(IpdInvestigation::class, 'ipd_id', 'ipd_id')->orderBy('order_date', 'desc');
    }

    public function medications()
    {
        return $this->hasMany(IpdMedication::class, 'ipd_id', 'ipd_id')->orderBy('created_at', 'desc');
    }

    public function advancePayments()
    {
        return $this->hasMany(IpdAdvancePayment::class, 'ipd_id', 'ipd_id')->orderBy('payment_date', 'desc');
    }

    public function bedTransfers()
    {
        return $this->hasMany(BedTransfer::class, 'ipd_id', 'ipd_id')->orderBy('transfer_datetime', 'desc');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    // Scopes
    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }

    public function scopeDischarged($query)
    {
        return $query->where('status', 'discharged');
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where(function ($q) use ($doctorId) {
            $q->where('treating_doctor_id', $doctorId)
              ->orWhere('consultant_doctor_id', $doctorId);
        });
    }

    public function scopeByWard($query, $wardId)
    {
        return $query->where('ward_id', $wardId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Calculate running bill
    public function calculateRunningBill()
    {
        $totalServices = $this->services()->where('is_billed', false)->sum('net_amount');
        $bedChargesPerDay = $this->bed->charges_per_day ?? $this->ward->charges_per_day ?? 0;
        $bedCharges = $this->los_days * $bedChargesPerDay;

        return [
            'services_total' => $totalServices,
            'bed_charges' => $bedCharges,
            'total' => $totalServices + $bedCharges,
            'advance_paid' => $this->advance_amount,
            'balance_due' => ($totalServices + $bedCharges) - $this->advance_amount,
        ];
    }

    // Generate IPD Number
    public static function generateIpdNumber($hospitalId)
    {
        $prefix = 'IPD';
        $year = date('Y');
        $month = date('m');

        $lastIpd = self::where('hospital_id', $hospitalId)
            ->whereYear('created_at', $year)
            ->orderBy('ipd_id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastIpd && preg_match('/(\d+)$/', $lastIpd->ipd_number, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }

        return $prefix . $year . $month . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
