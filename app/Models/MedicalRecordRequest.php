<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordRequest extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'request_number',
        'requester_type', 'requester_name', 'requester_contact', 'requester_organization',
        'request_purpose', 'records_requested', 'date_range_from', 'date_range_to',
        'request_date', 'priority', 'consent_type', 'consent_document_path',
        'status', 'approved_by', 'approved_at', 'rejection_reason', 'completed_at',
        'delivery_method', 'delivery_details', 'charges', 'created_by',
    ];

    protected $casts = [
        'records_requested' => 'array',
        'request_date' => 'date',
        'date_range_from' => 'date',
        'date_range_to' => 'date',
        'charges' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public static function generateRequestNumber(int $hospitalId): string
    {
        $prefix = 'MRR';
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
