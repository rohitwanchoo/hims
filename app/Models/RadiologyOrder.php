<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadiologyOrder extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'radiology_order_id';

    protected $fillable = [
        'hospital_id',
        'order_number',
        'patient_id',
        'opd_id',
        'ipd_id',
        'order_date',
        'order_time',
        'referring_doctor_id',
        'priority',
        'clinical_indication',
        'clinical_history',
        'pregnancy_status',
        'total_amount',
        'discount_amount',
        'net_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function opdVisit(): BelongsTo
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function ipdAdmission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function referringDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'referring_doctor_id', 'doctor_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RadiologyOrderDetail::class, 'radiology_order_id', 'radiology_order_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(RadiologyReport::class, 'radiology_order_id', 'radiology_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public static function generateOrderNumber(int $hospitalId): string
    {
        $prefix = 'RAD';
        $date = now()->format('Ymd');
        $count = static::where('hospital_id', $hospitalId)
            ->whereDate('created_at', today())
            ->count();
        return $prefix . $date . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
