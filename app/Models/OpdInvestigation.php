<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdInvestigation extends Model
{
    protected $primaryKey = 'investigation_id';

    protected $fillable = [
        'opd_id',
        'investigation_type',
        'test_id',
        'service_id',
        'investigation_name',
        'rate',
        'clinical_notes',
        'priority',
        'status',
        'ordered_by',
        'ordered_at',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class, 'test_id', 'test_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function orderedByUser()
    {
        return $this->belongsTo(User::class, 'ordered_by', 'id');
    }
}
