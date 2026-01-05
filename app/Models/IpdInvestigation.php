<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class IpdInvestigation extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'investigation_id';

    protected $fillable = [
        'hospital_id',
        'ipd_id',
        'order_date',
        'order_time',
        'investigation_type',
        'test_id',
        'investigation_name',
        'priority',
        'clinical_notes',
        'rate',
        'status',
        'result',
        'result_datetime',
        'ordered_by',
        'lab_order_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'result_datetime' => 'datetime',
        'rate' => 'decimal:2',
    ];

    protected $appends = ['ordered_by_name'];

    public function getOrderedByNameAttribute()
    {
        return $this->orderedByDoctor ? $this->orderedByDoctor->doctor_name : null;
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function test()
    {
        return $this->belongsTo(LabTest::class, 'test_id', 'test_id');
    }

    public function orderedByDoctor()
    {
        return $this->belongsTo(Doctor::class, 'ordered_by', 'doctor_id');
    }

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id', 'order_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['ordered', 'sample_collected', 'processing']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('investigation_type', $type);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('priority', ['urgent', 'stat']);
    }
}
