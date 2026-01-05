<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class RateChangeRequest extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'hospital_id',
        'opd_id',
        'service_id',
        'original_rate',
        'requested_rate',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'original_rate' => 'decimal:2',
        'requested_rate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    /**
     * Approve the rate change request
     */
    public function approve($userId, $remarks = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'remarks' => $remarks,
        ]);

        // Apply the rate change if linked to OPD service
        if ($this->opd_id && $this->service_id) {
            OpdVisitService::where('opd_id', $this->opd_id)
                ->where('service_id', $this->service_id)
                ->update(['rate' => $this->requested_rate]);
        }

        return $this;
    }

    /**
     * Reject the rate change request
     */
    public function reject($userId, $remarks = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'remarks' => $remarks,
        ]);

        return $this;
    }
}
