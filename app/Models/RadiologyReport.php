<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyReport extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'report_id';

    protected $fillable = [
        'hospital_id',
        'radiology_order_id',
        'detail_id',
        'patient_id',
        'reporting_radiologist_id',
        'report_date',
        'report_time',
        'technique',
        'findings',
        'impression',
        'recommendations',
        'critical_finding',
        'critical_finding_communicated',
        'communicated_to',
        'communicated_at',
        'report_status',
        'verified_by',
        'verified_at',
        'report_pdf_path',
    ];

    protected $casts = [
        'report_date' => 'date',
        'critical_finding' => 'boolean',
        'critical_finding_communicated' => 'boolean',
        'communicated_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id', 'radiology_order_id');
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrderDetail::class, 'detail_id', 'detail_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'reporting_radiologist_id', 'doctor_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'verified_by', 'doctor_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RadiologyImage::class, 'report_id', 'report_id');
    }
}
